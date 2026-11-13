<?php

namespace Modules\TravelExpenseReport\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;
use Modules\AnnualTicket\Models\AnnualTicket;
use Modules\TravelExpenseReport\Models\TravelExpenseReport;
use Modules\TravelExpenseReport\Repositories\TravelExpenseReportRepository;
use Modules\UserManagement\Models\User;
use Spatie\Activitylog\Models\Activity;

class TravelExpenseReportService
{
    public function __construct(
        private TravelExpenseReportRepository $repository,
    ) {}

    public function listFor(User $user)
    {
        return $this->repository->allForEmployee($user);
    }

    public function findFor(User $user, int $id): TravelExpenseReport
    {
        return $this->repository->findByIdForEmployee($user, $id);
    }

    // Aturan claim sesuai kesepakatan flow:
    // - Direct Payment (same/different route) -> Ticket Reimbursement (reimbursable)
    // - Booked by Company + Same Route        -> tidak claimable
    // - Booked by Company + Different Route   -> Excess Settlement (employee bayar selisih ke Finance)
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
            $payload = $this->applySubmission($ticket, $payload, $attachmentFiles, $proofOfPayment);
            $payload['status'] = TravelExpenseReport::STATUS_SUBMITTED;
            $payload['document_number'] = $this->repository->generateDocumentNumber();
            $payload['submitted_at'] = now();
        }

        $report = $this->repository->create($user, $ticket, $payload, $attachmentFiles, $proofOfPayment);

        if ($report->status === TravelExpenseReport::STATUS_SUBMITTED) {
            $this->logActivity($report, $user, 'submitted', 'Travel Expense Report submitted', ['role' => $this->submitterRoleName($user)]);
        }

        return $report;
    }

    // Update hanya untuk draft yang belum disubmit
    public function updateDraft(User $user, int $id, array $data, array $attachmentFiles = [], $proofOfPayment = null): TravelExpenseReport
    {
        $report = $this->repository->findByIdForEmployee($user, $id);

        abort_unless($report->status === TravelExpenseReport::STATUS_DRAFT, 422, 'Only draft Travel Expense Reports can be updated.');

        $ticket = $report->annualTicket()->with(['legs', 'fareReferences', 'booking'])->firstOrFail();

        $payload = [
            'currency' => $this->resolveCurrency($ticket),
            'total_fare_reference' => $this->fareReferenceTotal($ticket),
            ...($report->claim_type === TravelExpenseReport::CLAIM_TICKET_REIMBURSEMENT ? ['total_actual_amount' => $data['total_actual_amount'] ?? $report->total_actual_amount] : []),
        ];

        return $this->repository->update($report, $payload, $attachmentFiles, $proofOfPayment);
    }

    // Submit draft yang sudah ada: validasi kelengkapan lalu finalisasi nominal
    public function submit(User $user, int $id): TravelExpenseReport
    {
        $report = $this->repository->findByIdForEmployee($user, $id);

        abort_unless($report->status === TravelExpenseReport::STATUS_DRAFT, 422, 'This Travel Expense Report has already been submitted.');

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

        $this->logActivity($report, $user, 'submitted', 'Travel Expense Report submitted', ['role' => $this->submitterRoleName($user)]);

        return $report;
    }

    // Role asli submitter yang tampil di history log - sama seperti module lain (bukan label generik)
    private function submitterRoleName(User $user): string
    {
        return $user->roles->first()?->name ?? 'Employee';
    }

    public function historyById(int $id, User $user): array
    {
        return $this->history($this->repository->findByIdForEmployee($user, $id));
    }

    public function history(TravelExpenseReport $report): array
    {
        return Activity::with('causer')
            ->where('log_name', 'travel-expense-report')
            ->where('subject_type', $report->getMorphClass())
            ->where('subject_id', $report->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'event' => $log->event,
                'description' => $log->description,
                'round' => $log->properties->get('round', 1),
                'role' => $log->properties->get('role'),
                'note' => $log->properties->get('note'),
                'remark' => $log->properties->get('remark'),
                'causer' => $log->causer?->name,
                'created_at' => $log->created_at,
            ])
            ->values()
            ->all();
    }

    public function pdf(int $id, User $user)
    {
        $report = $this->repository->findByIdForEmployee($user, $id);

        return Pdf::loadView('travelexpensereport::pdf.travel_expense_report', ['report' => $report])->setPaper('a4');
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
