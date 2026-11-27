<?php

namespace Modules\TravelExpenseReport\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\AnnualTicket\Models\AnnualTicket;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\MasterDivision;
use Modules\MasterManagement\Models\Section;
use Modules\TravelExpenseReport\Models\TravelExpenseReport;
use Modules\TravelExpenseReport\Models\TravelExpenseReportExpense;
use Modules\TravelExpenseReport\Repositories\TravelExpenseReportRepository;
use Modules\TravelOrder\Models\TravelOrder;
use Modules\UserManagement\Models\User;
use Spatie\Activitylog\Models\Activity;

class TravelExpenseReportService
{
    public function __construct(
        private TravelExpenseReportRepository $repository,
        private TravelExpenseReportApprovalService $approvalService,
    ) {}

    public function listFor(User $user)
    {
        return $this->repository->allVisibleFor($user)
            ->each(fn (TravelExpenseReport $report) => $this->attachFlowAttributes($report, $user));
    }

    public function findFor(User $user, int $id): TravelExpenseReport
    {
        return $this->attachFlowAttributes($this->repository->findByIdFor($user, $id), $user);
    }

    // Ambil TER tanpa scope visibilitas - dipakai untuk response setelah aksi approval sukses
    public function findById(int $id): TravelExpenseReport
    {
        return $this->repository->findById($id);
    }

    // Info tahap approval saat ini untuk tombol aksi approver di frontend
    private function attachFlowAttributes(TravelExpenseReport $report, User $user): TravelExpenseReport
    {
        $stage = $this->approvalService->currentStage($report);
        $step = $stage?->matrixStep;

        $report->setAttribute('can_process', $this->approvalService->canProcess($report, $user));
        $report->setAttribute('is_owner', $report->employee_id === $user->id);
        $report->setAttribute('stage_action', $stage?->action_type);
        $report->setAttribute('stage_role', $stage?->role?->name);
        // Tahap Finance (unit step milik department Finance & Accounting: area/section/dept tsb)
        // - dipakai frontend untuk membuka PDF langsung saat klik View
        $unitDepartment = $step?->section?->department ?? $step?->department;
        $report->setAttribute('stage_finance', $unitDepartment && $unitDepartment->name === 'Finance & Accounting');

        return $report;
    }

    public function create(User $user, array $data, array $attachmentFiles = [], $proofOfPayment = null): TravelExpenseReport
    {
        $ticket = $this->claimableTicketFor($user, (int) $data['annual_ticket_id']);

        abort_if($this->repository->findByAnnualTicketId($ticket->id), 422, 'A Travel Expense Report already exists for this Annual Ticket.');

        $claimType = $this->resolveClaimType($ticket);
        $fareReference = $this->fareReferenceTotal($ticket);
        $currency = $this->resolveCurrency($ticket);

        $payload = [
            'employee_id' => $user->id,
            'claim_type' => $claimType,
            'status' => TravelExpenseReport::STATUS_DRAFT,
            'currency' => $currency,
            'total_fare_reference' => $fareReference,
            'total_actual_amount' => $claimType === TravelExpenseReport::CLAIM_TICKET_REIMBURSEMENT ? ($data['total_actual_amount'] ?? null) : null,
        ];

        if (($data['action'] ?? 'draft') === 'submit') {
            $this->approvalService->assertMatrixFor($user);

            $payload = $this->applySubmission($ticket, $payload, $attachmentFiles, $proofOfPayment);
            $payload['status'] = TravelExpenseReport::STATUS_SUBMITTED;
            $payload['document_number'] = $this->repository->generateDocumentNumber();
            $payload['submitted_at'] = now();
        }

        $report = $this->repository->create($user, $ticket, $payload, $attachmentFiles, $proofOfPayment);

        if ($report->status === TravelExpenseReport::STATUS_SUBMITTED) {
            $this->startApprovalFlow($report);
        }

        return $report;
    }

    // Update hanya untuk draft / yang dikembalikan untuk revisi
    public function updateDraft(User $user, int $id, array $data, array $attachmentFiles = [], $proofOfPayment = null, bool $removeProofOfPayment = false): TravelExpenseReport
    {
        $report = $this->repository->findByIdForEmployee($user, $id);

        abort_unless(in_array($report->status, [TravelExpenseReport::STATUS_DRAFT, TravelExpenseReport::STATUS_REVISION_REQUIRED], true), 422, 'Only draft or revision-required Travel Expense Reports can be updated.');

        $ticket = $report->annualTicket()->with(['legs', 'fareReferences', 'booking'])->firstOrFail();

        $payload = [
            'currency' => $this->resolveCurrency($ticket),
            'total_fare_reference' => $this->fareReferenceTotal($ticket),
            ...($report->claim_type === TravelExpenseReport::CLAIM_TICKET_REIMBURSEMENT ? ['total_actual_amount' => $data['total_actual_amount'] ?? $report->total_actual_amount] : []),
        ];

        // Mau ganti proof: file lama dihapus dari storage, column dikosongkan (file baru menggantikan via $proofOfPayment)
        if ($removeProofOfPayment && ! $proofOfPayment && $report->proof_of_payment_path) {
            Storage::disk('public')->delete($report->proof_of_payment_path);
            $payload['proof_of_payment_path'] = null;
        }

        return $this->repository->update($report, $payload, $attachmentFiles, $proofOfPayment);
    }

    // Hapus lampiran lama saat draft / revisi - file fisik ikut dihapus.
    // Sudah terhapus sebelumnya (mis. klik ganda) -> dianggap sukses juga, tanpa error
    public function deleteAttachment(User $user, int $id, int $attachmentId): void
    {
        $report = $this->repository->findByIdForEmployee($user, $id);

        abort_unless(in_array($report->status, [TravelExpenseReport::STATUS_DRAFT, TravelExpenseReport::STATUS_REVISION_REQUIRED], true), 422, 'Only draft or revision-required Travel Expense Reports can be modified.');

        $attachment = $report->attachments()->whereKey($attachmentId)->first();

        if (! $attachment) {
            return;
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
    }

    // Submit draft (atau hasil revisi) yang sudah ada: validasi kelengkapan lalu finalisasi nominal
    public function submit(User $user, int $id): TravelExpenseReport
    {
        $report = $this->repository->findByIdForEmployee($user, $id);

        abort_unless(in_array($report->status, [TravelExpenseReport::STATUS_DRAFT, TravelExpenseReport::STATUS_REVISION_REQUIRED], true), 422, 'This Travel Expense Report has already been submitted.');

        $this->approvalService->assertMatrixFor($user);

        $ticket = $report->annualTicket()->with(['legs', 'fareReferences', 'booking'])->firstOrFail();

        $attachmentFiles = [];
        $payload = $this->applySubmission(
            $ticket,
            [
                'claim_type' => $report->claim_type,
                'currency' => $report->currency,
                'total_fare_reference' => $report->total_fare_reference,
                'total_actual_amount' => $report->total_actual_amount,
            ],
            $attachmentFiles,
            null,
            $report,
        );

        $report = $this->repository->update($report, [
            ...$payload,
            'status' => TravelExpenseReport::STATUS_SUBMITTED,
            'document_number' => $report->document_number ?? $this->repository->generateDocumentNumber(),
            'submitted_at' => now(),
        ]);

        $this->startApprovalFlow($report);

        return $report;
    }

    // Masukkan TER ke rantai approval sesuai authority matrix - matrix belum ada -> 422 dengan pesan jelas
    private function startApprovalFlow(TravelExpenseReport $report): void
    {
        try {
            $this->approvalService->initFlow($report);
        } catch (\RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }
    }

    // TER tidak lagi di tahap user (sudah diproses/maju tahap) -> 422 dengan pesan jelas, bukan 404
    private function reportForAction(User $user, int $id): TravelExpenseReport
    {
        try {
            return $this->repository->findByIdFor($user, $id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            abort(422, 'This Travel Expense Report is no longer waiting at your approval stage.');
        }
    }

    public function check(User $user, int $id, ?string $note = null): void
    {
        $this->approvalService->check($this->reportForAction($user, $id), $user, $note);
    }

    public function approve(User $user, int $id, ?string $note = null): void
    {
        $this->approvalService->approve($this->reportForAction($user, $id), $user, $note);
    }

    public function revision(User $user, int $id, string $remark): void
    {
        $this->approvalService->revision($this->reportForAction($user, $id), $user, $remark);
    }

    public function historyById(int $id, User $user): array
    {
        return $this->history($this->repository->findByIdFor($user, $id));
    }

    public function history(TravelExpenseReport $report): array
    {
        $approvals = $report->approvals()->with('matrixStep.section', 'matrixStep.department')->get()->keyBy('sequence');
        [$section, $department, $division] = $this->requesterOrganization($report->employee_id);

        return Activity::with('causer')
            ->where('log_name', 'travel-expense-report')
            ->where('subject_type', $report->getMorphClass())
            ->where('subject_id', $report->id)
            ->orderBy('id')
            ->get()
            ->map(function ($log) use ($approvals, $section, $department, $division) {
                $step = $approvals->get($log->properties->get('sequence'))?->matrixStep;

                return [
                    'id' => $log->id,
                    'event' => $log->event,
                    'description' => $log->description,
                    'round' => $log->properties->get('round', 1),
                    // Label role gaya Travel Order: "Unit (Role)" - unit dari step matrix, fallback org pengaju
                    'role' => $this->historyRoleLabel(
                        $log->properties->get('role'),
                        $step?->section?->name ?? (filled($step?->area) ? $step->area : null) ?? $step?->department?->name,
                        $section,
                        $department,
                        $division,
                    ),
                    'note' => $log->properties->get('note'),
                    'remark' => $log->properties->get('remark'),
                    'causer' => $log->causer?->name,
                    'created_at' => $log->created_at,
                ];
            })
            ->values()
            ->all();
    }

    // Konteks organisasi pengaju: section (jika sect head), department, division
    private function requesterOrganization(int $employeeId): array
    {
        $section = Section::with('department')->where('section_head_id', $employeeId)->first();
        $department = $section?->department
            ?? Department::where('dept_head_id', $employeeId)->first()
            ?? Department::where('dept_admin_id', $employeeId)->first();
        $division = $department ? MasterDivision::find($department->division_head_id) : null;

        return [$section, $department, $division];
    }

    // Label role gaya Travel Order/PDF: "Unit (Role)" - Sect Head pakai section, Dept Head department, Div Head division
    private function historyRoleLabel(?string $role, ?string $unit, $section, $department, $division): string
    {
        $display = $role ? str_replace(' ', '.', $role) : '-';

        $organization = $unit ?? match (trim((string) $role)) {
            'Sect Head' => $section?->name,
            'Dept Head' => $department?->name,
            'Div Head' => $division?->division_name,
            default => null,
        };

        return $organization ? "{$organization} ({$display})" : $display;
    }

    public function pdf(int $id, User $user)
    {
        $report = $this->repository->findByIdFor($user, $id);

        return Pdf::loadView('travelexpensereport::pdf.travel_expense_report', [
            'report' => $report,
            // Rantai approval putaran terakhir - mengisi tabel Reviewed & Approved By
            'approvals' => $this->approvalService->latestRoundApprovals($report),
        ])->setPaper('a4');
    }

    // Activity log TER - aktivitas tercatat mulai dari submit, submit membuka round baru
    public function logActivity(TravelExpenseReport $report, ?User $causer, string $event, string $description, array $props = []): void
    {
        $submittedCount = Activity::where('log_name', 'travel-expense-report')
            ->where('subject_type', $report->getMorphClass())
            ->where('subject_id', $report->id)
            ->where('event', 'submitted')
            ->count();

        $round = max(1, $submittedCount + ($event === 'submitted' ? 1 : 0));

        activity('travel-expense-report')
            ->performedOn($report)
            ->causedBy($causer)
            ->event($event)
            ->withProperties(array_merge($props, ['round' => $round]))
            ->log($description);
    }

    public function deleteDraft(User $user, int $id): void
    {
        $report = $this->repository->findByIdForEmployee($user, $id);

        abort_unless($report->status === TravelExpenseReport::STATUS_DRAFT, 422, 'Only draft Travel Expense Reports can be deleted.');

        $this->repository->delete($report);
    }

    // ===== TER Travel Order (business trip individu & grup) =====

    // Simpan draft / submit TER berbasis Travel Order dengan rincian per hari (normalized days + expenses)
    public function storeTravelOrderReport(User $user, array $data, array $days, array $attachmentFilesByKey = []): TravelExpenseReport
    {
        $order = $this->repository->findClaimableTravelOrderFor($user, (int) $data['travel_order_id']);

        $existing = $this->repository->findByTravelOrderId($order->id);

        if ($existing) {
            abort_unless(
                in_array($existing->status, [TravelExpenseReport::STATUS_DRAFT, TravelExpenseReport::STATUS_REVISION_REQUIRED], true),
                422,
                'A Travel Expense Report already exists for this Travel Order.',
            );
        }

        $preparedDays = $this->prepareTravelOrderDays($days);
        $submit = ($data['action'] ?? 'draft') === 'submit';

        if ($submit) {
            $this->assertTravelOrderDaysSubmittable($preparedDays, $attachmentFilesByKey);
            // Alur approval mengikuti matrix TER per travel region TO
            $this->approvalService->assertMatrixFor($user, $order->travel_region);
        }

        $advance = $order->advance;
        $totals = $this->totalsFromTravelOrderDays($preparedDays);
        $advanceTotal = (float) ($advance?->meal_allowance ?? 0) + (float) ($advance?->pocket_money ?? 0);

        $payload = [
            'status' => TravelExpenseReport::STATUS_DRAFT,
            // Advance hanya untuk IDR (Domestic) - region non-IDR tidak menerima advance
            'currency' => ($advance?->currency && $advance->currency !== 'IDR') ? $advance->currency : 'IDR',
            'advance_meal_allowance' => round((float) ($advance?->meal_allowance ?? 0), 2),
            'advance_pocket_money' => round((float) ($advance?->pocket_money ?? 0), 2),
            'total_eligible' => $totals['eligible'],
            'total_actual_amount' => $totals['actual'],
        ];

        if ($submit) {
            $payload['status'] = TravelExpenseReport::STATUS_SUBMITTED;
            $payload['document_number'] = $this->repository->generateDocumentNumber();
            $payload['submitted_at'] = now();
            // Positif = kembali ke perusahaan, negatif = reimburse ke karyawan
            $payload['settlement_amount'] = round($advanceTotal - $totals['eligible'], 2);
        }

        $report = $this->repository->saveTravelOrderReport($user, $order, $payload, $preparedDays, $attachmentFilesByKey);

        if ($submit) {
            $this->startApprovalFlow($report);
        }

        return $report;
    }

    // Normalisasi payload days dari FE: buang expense kosong & rapikan field per item
    private function prepareTravelOrderDays(array $days): array
    {
        return collect($days)->values()->map(function ($day, $index) {
            return [
                'day_number' => (int) ($day['day_number'] ?? ($index + 1)),
                'date' => $day['date'],
                'type' => $day['type'] ?? 'stay',
                'effective_time' => $day['effective_time'] ?? null,
                'different_countries' => (bool) ($day['different_countries'] ?? false),
                'expenses' => collect($day['expenses'] ?? [])->filter(fn ($expense) => $this->travelOrderExpenseClaimed($expense))->values()->map(function ($expense) {
                    return [
                        'category' => $expense['category'],
                        'item_index' => (int) ($expense['item_index'] ?? 1),
                        'kind' => $expense['kind'] ?? null,
                        'description' => $expense['description'] ?? null,
                        'currency' => $expense['currency'] ?? 'IDR',
                        'actual_amount' => round((float) ($expense['actual_amount'] ?? 0), 2),
                        'eligible_amount' => isset($expense['eligible_amount']) && $expense['eligible_amount'] !== '' ? round((float) $expense['eligible_amount'], 2) : null,
                        'manual_review' => (bool) ($expense['manual_review'] ?? false),
                        'payment_mode' => $expense['payment_mode'] ?? TravelExpenseReportExpense::PAYMENT_MYSELF,
                        'paid_by' => $expense['paid_by'] ?? null,
                        'paid_for' => array_values(array_filter((array) ($expense['paid_for'] ?? []))),
                        'dinner_type' => $expense['dinner_type'] ?? null,
                        'justification' => $expense['justification'] ?? null,
                        'hotel_name' => $expense['hotel_name'] ?? null,
                        'room_type' => $expense['room_type'] ?? null,
                        'hotel_standard' => $expense['hotel_standard'] ?? null,
                        'attachment_key' => $expense['attachment_key'] ?? null,
                        'existing_attachment_path' => $expense['existing_attachment_path'] ?? null,
                        'existing_attachment_name' => $expense['existing_attachment_name'] ?? null,
                    ];
                })->all(),
            ];
        })->filter(fn ($day) => count($day['expenses']) > 0 || $day['day_number'] === 1)->values()->all();
    }

    // Expense ikut disimpan saat benar-benar diklaim (amount > 0), catatan payment arrangement grup,
    // atau entitlement tetap per hari (fixed basis) yang tetap direkam walaupun tanpa aktual
    private function travelOrderExpenseClaimed(array $expense): bool
    {
        $mode = $expense['payment_mode'] ?? TravelExpenseReportExpense::PAYMENT_MYSELF;

        if ($mode === TravelExpenseReportExpense::PAYMENT_PAID_BY_OTHER) {
            return true;
        }

        if ((float) ($expense['actual_amount'] ?? 0) > 0) {
            return true;
        }

        return in_array($expense['category'] ?? '', TravelExpenseReportExpense::FIXED_CATEGORIES, true)
            && isset($expense['eligible_amount'])
            && $expense['eligible_amount'] !== null
            && $expense['eligible_amount'] !== '';
    }

    // Validasi kelengkapan sebelum submit - draft boleh belum lengkap
    private function assertTravelOrderDaysSubmittable(array $days, array $attachmentFilesByKey): void
    {
        $claimedTotal = 0;

        foreach ($days as $day) {
            foreach ($day['expenses'] as $expense) {
                $claimedTotal++;
                $this->assertTravelOrderExpense($expense, $attachmentFilesByKey);
            }
        }

        abort_unless($claimedTotal > 0, 422, 'At least one expense must be claimed before submitting the Travel Expense Report.');
    }

    private function assertTravelOrderExpense(array $expense, array $attachmentFilesByKey): void
    {
        $mode = $expense['payment_mode'];
        $category = $expense['category'];

        abort_unless(in_array($category, TravelExpenseReportExpense::CATEGORIES, true), 422, "The expense category \"{$category}\" is not recognized.");

        abort_unless(in_array($mode, TravelExpenseReportExpense::PAYMENT_MODES, true), 422, "The payment arrangement \"{$mode}\" is not recognized.");

        if ($mode === TravelExpenseReportExpense::PAYMENT_PAID_BY_OTHER) {
            abort_unless(filled($expense['paid_by']), 422, "The payer is required for a \"Paid by Other\" expense ({$category}, day {$expense['day_number']}).");

            return;
        }

        // Entitlement tetap (fixed basis) boleh tanpa aktual - barisnya merekam hak entitlement per hari
        $isFixed = in_array($category, TravelExpenseReportExpense::FIXED_CATEGORIES, true);

        abort_if(! $isFixed && (float) $expense['actual_amount'] <= 0, 422, "The claimed amount is required for {$category} on day {$expense['day_number']}.");

        abort_if((float) $expense['actual_amount'] > 0 && ! filled($expense['description']), 422, "The description is required for {$category} on day {$expense['day_number']}.");

        if ($category === 'dinner' && ($expense['dinner_type'] ?? '') === 'Dinner at Hotel') {
            abort_unless(filled($expense['justification']), 422, 'The justification is required for a Dinner at Hotel claim.');
        }

        if ($mode === TravelExpenseReportExpense::PAYMENT_PAY_FOR_OTHERS) {
            abort_unless(count($expense['paid_for']) > 0, 422, "Select at least one employee covered for a \"Pay for Others\" expense ({$category}, day {$expense['day_number']}).");
        }

        if (in_array($category, TravelExpenseReportExpense::RECEIPT_CATEGORIES, true)) {
            // Struk baru yang diupload ATAU file struk lama dari draft yang dipakai ulang
            $hasFile = (($expense['attachment_key'] ?? null) && isset($attachmentFilesByKey[$expense['attachment_key']]))
                || filled($expense['existing_attachment_path'] ?? null);

            abort_unless($hasFile, 422, "The supporting document is required for {$category} on day {$expense['day_number']}.");
        }
    }

    // Total actual & eligible hanya dari expense yang diklaim (paid_by_other tidak masuk hitungan)
    private function totalsFromTravelOrderDays(array $days): array
    {
        $expenses = collect($days)->flatMap(fn ($day) => $day['expenses'])
            ->filter(fn ($expense) => $expense['payment_mode'] !== TravelExpenseReportExpense::PAYMENT_PAID_BY_OTHER);

        return [
            'actual' => round($expenses->sum(fn ($expense) => (float) $expense['actual_amount']), 2),
            'eligible' => round($expenses->sum(fn ($expense) => (float) ($expense['eligible_amount'] ?? 0)), 2),
        ];
    }

    // Tiket harus milik user dan sudah diproses GMO (processed_by_gmo / partially_cancelled)
    private function claimableTicketFor(User $user, int $ticketId): AnnualTicket
    {
        $ticket = $this->repository->findClaimableTicketFor($user, $ticketId);

        abort_unless(in_array($ticket->status, TravelExpenseReportRepository::CLAIMABLE_TICKET_STATUSES, true), 422, 'This Annual Ticket is not eligible for a Travel Expense Report yet.');

        return $ticket;
    }

    private function resolveClaimType(AnnualTicket $ticket): string
    {
        if ($ticket->ticket_arrangement === 'Direct Payment') {
            return TravelExpenseReport::CLAIM_TICKET_REIMBURSEMENT;
        }

        // Booked by Company + Different Route -> excess, Booked by Company + Same Route -> tidak ada claim
        abort_unless($ticket->route_condition === 'Different Route', 422, 'Booked by Company with Same Route is not reimbursable and cannot be claimed.');

        return TravelExpenseReport::CLAIM_EXCESS_SETTLEMENT;
    }

    // Total fare reference dihitung ulang dari data GMO: fare per direction x jumlah leg per direction
    private function fareReferenceTotal(AnnualTicket $ticket): float
    {
        $total = (float) $ticket->fareReferences->sum(
            fn ($reference) => (float) $reference->fare_amount * $ticket->legs->where('direction', $reference->direction)->count(),
        );

        abort_unless($total > 0, 422, 'The fare reference for this Annual Ticket is not available yet.');

        return round($total, 2);
    }

    private function resolveCurrency(AnnualTicket $ticket): string
    {
        return $ticket->fareReferences->first()?->currency
            ?? $ticket->booking?->amount_currency
            ?? 'IDR';
    }

    // Finalisasi nominal saat submit - nilai settlement/excess selalu dihitung server, bukan dari client
    private function applySubmission(AnnualTicket $ticket, array $payload, array $attachmentFiles, $proofOfPayment, ?TravelExpenseReport $report = null): array
    {
        $fareReference = (float) $payload['total_fare_reference'];

        if ($payload['claim_type'] === TravelExpenseReport::CLAIM_TICKET_REIMBURSEMENT) {
            $actual = Validator::make($payload, [
                'total_actual_amount' => ['required', 'numeric', 'gt:0'],
            ])->validate();

            $storedAttachments = $report ? $report->attachments()->count() : count($attachmentFiles);

            abort_unless($storedAttachments > 0, 422, 'At least one supporting document (E-Ticket, Boarding Pass, or Invoice) is required.');

            return [
                ...$payload,
                'total_actual_amount' => round((float) $actual['total_actual_amount'], 2),
                // Reimburse hanya sampai batas fare reference - kelebihan tidak diganti
                'settlement_amount' => round(min((float) $actual['total_actual_amount'], $fareReference), 2),
            ];
        }

        $hasProof = $report ? (bool) $report->proof_of_payment_path : (bool) $proofOfPayment;

        abort_unless($hasProof, 422, 'The proof of excess payment to Finance is required.');

        $bookingAmount = (float) $ticket->booking?->amount;

        abort_unless($bookingAmount > 0, 422, 'The company booking amount for this Annual Ticket is not available.');

        return [
            ...$payload,
            'company_booking_amount' => round($bookingAmount, 2),
            // Selisih yang harus dibayar employee ke Finance: booking - fare reference
            'employee_excess' => round(max(0, $bookingAmount - $fareReference), 2),
        ];
    }
}
