<?php

namespace Modules\TravelExpenseReport\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\AnnualTicket\Models\AnnualTicket;
use Modules\TravelExpenseReport\Models\TravelExpenseReport;
use Modules\UserManagement\Models\User;

class TravelExpenseReportRepository
{
    // Status tiket yang sudah boleh dibuatkan TER (sudah lewat pemrosesan GMO)
    public const CLAIMABLE_TICKET_STATUSES = ['processed_by_gmo', 'partially_cancelled'];

    public const EAGER_LOAD = [
        'attachments',
        'employee',
        'annualTicket.legs',
        'annualTicket.fareReferences',
        'annualTicket.booking',
        'annualTicket.employee.eligibleDestination',
    ];

    public function allForEmployee(User $user): Collection
    {
        return $this->baseQuery()->where('employee_id', $user->id)->latest()->get();
    }

    // Admin melihat semua pengajuan; employee hanya miliknya; approver melihat yang pending di tahap role-nya
    public function allVisibleFor(User $user): Collection
    {
        return $this->baseQuery()
            ->when(! $user->hasRole('Admin'), fn ($query) => $query->where($this->visibilityScope($user)))
            ->latest()
            ->get();
    }

    public function findByIdForEmployee(User $user, int $id): TravelExpenseReport
    {
        return $this->baseQuery()->where('employee_id', $user->id)->findOrFail($id);
    }

    // Detail/history/pdf boleh diakses admin untuk TER semua employee
    public function findByIdFor(User $user, int $id): TravelExpenseReport
    {
        return $this->baseQuery()
            ->when(! $user->hasRole('Admin'), fn ($query) => $query->where($this->visibilityScope($user)))
            ->findOrFail($id);
    }

    private function visibilityScope(User $user): \Closure
    {
        return function ($query) use ($user) {
            $resolver = app(\Modules\AuthorityMatrix\Services\MatrixStepApproverResolver::class);

            $query->where('employee_id', $user->id)
                ->orWhereHas('approvals', function ($approvals) use ($user, $resolver) {
                    $approvals->where('status', 'pending')
                        // Hanya tahap yang sedang berjalan - step pending lebih awal tidak dihitung
                        ->whereNotExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('travel_expense_report_approvals as prev')
                                ->whereColumn('prev.travel_expense_report_id', 'travel_expense_report_approvals.travel_expense_report_id')
                                ->where('prev.status', 'pending')
                                ->whereColumn('prev.sequence', '<', 'travel_expense_report_approvals.sequence');
                        })
                        // Step matrix menentukan siapa approver-nya: approval lama (tanpa step) atau
                        // step tanpa batasan -> pemilik role; step terbatas -> hanya user yang ditugaskan
                        ->where(function ($stages) use ($user, $resolver) {
                            $stages->where(function ($legacy) use ($user) {
                                $legacy->whereNull('authority_matrix_step_id')
                                    ->whereHas('role', fn ($roles) => $roles->whereIn('name', $user->getRoleNames()));
                            })->orWhereIn('role_id', $resolver->unconstrainedRoleIdsFor($user))
                                ->orWhereIn('authority_matrix_step_id', $resolver->stepIdsMatchingUser($user));
                        });
                });
        };
    }

    private function baseQuery()
    {
        return TravelExpenseReport::with(self::EAGER_LOAD);
    }

    // Setelah aksi approval sukses, TER sudah bukan di tahap user - ambil tanpa scope untuk response
    public function findById(int $id): TravelExpenseReport
    {
        return $this->baseQuery()->findOrFail($id);
    }

    public function findByAnnualTicketId(int $ticketId): ?TravelExpenseReport
    {
        return TravelExpenseReport::where('annual_ticket_id', $ticketId)->first();
    }

    public function findClaimableTicketFor(User $user, int $ticketId): AnnualTicket
    {
        return AnnualTicket::with(['legs', 'fareReferences', 'booking'])
            ->where('employee_id', $user->id)
            ->findOrFail($ticketId);
    }

    // Simpan TER beserta file lampiran/proof dalam satu transaction.
    // Jika gagal di tengah jalan, file yang sudah tersimpan dihapus (pola sama seperti AnnualTicketService).
    public function create(User $user, AnnualTicket $ticket, array $data, array $attachmentFiles = [], $proofOfPayment = null): TravelExpenseReport
    {
        $newFiles = [];

        try {
            $report = DB::transaction(function () use ($ticket, $data, $attachmentFiles, $proofOfPayment, &$newFiles) {
                $report = TravelExpenseReport::create([
                    'employee_id' => $data['employee_id'],
                    'source_type' => TravelExpenseReport::SOURCE_ANNUAL_TICKET,
                    'annual_ticket_id' => $ticket->id,
                    'claim_type' => $data['claim_type'],
                    'status' => $data['status'],
                    'document_number' => $data['document_number'] ?? null,
                    'currency' => $data['currency'],
                    'total_fare_reference' => $data['total_fare_reference'],
                    'total_actual_amount' => $data['total_actual_amount'] ?? null,
                    'settlement_amount' => $data['settlement_amount'] ?? null,
                    'company_booking_amount' => $data['company_booking_amount'] ?? null,
                    'employee_excess' => $data['employee_excess'] ?? null,
                    'submitted_at' => $data['submitted_at'] ?? null,
                ]);

                foreach ($attachmentFiles as $file) {
                    $newFiles[] = $path = $file->store('travel-expense-reports/attachments', 'public');

                    $report->attachments()->create([
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }

                if ($proofOfPayment) {
                    $newFiles[] = $path = $proofOfPayment->store('travel-expense-reports/proof-of-payments', 'public');

                    $report->update(['proof_of_payment_path' => $path]);
                }

                return $report;
            });
        } catch (\Throwable $exception) {
            foreach ($newFiles as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        return $report->load('attachments');
    }

    public function update(TravelExpenseReport $report, array $data, array $attachmentFiles = [], $proofOfPayment = null): TravelExpenseReport
    {
        $newFiles = [];
        $oldProof = $proofOfPayment ? $report->proof_of_payment_path : null;

        try {
            DB::transaction(function () use ($report, $data, $attachmentFiles, $proofOfPayment, &$newFiles) {
                $report->update(collect($data)->only([
                    'claim_type', 'status', 'document_number', 'currency', 'total_fare_reference',
                    'total_actual_amount', 'settlement_amount', 'company_booking_amount', 'employee_excess',
                    'proof_of_payment_path', 'submitted_at',
                ])->all());

                foreach ($attachmentFiles as $file) {
                    $newFiles[] = $path = $file->store('travel-expense-reports/attachments', 'public');

                    $report->attachments()->create([
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }

                if ($proofOfPayment) {
                    $newFiles[] = $path = $proofOfPayment->store('travel-expense-reports/proof-of-payments', 'public');

                    $report->update(['proof_of_payment_path' => $path]);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($newFiles as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        // Proof lama dihapus hanya setelah transaction & penyimpanan file baru sukses
        if ($oldProof) {
            Storage::disk('public')->delete($oldProof);
        }

        return $report->load('attachments');
    }

    public function addAttachments(TravelExpenseReport $report, array $attachmentFiles): void
    {
        foreach ($attachmentFiles as $file) {
            $report->attachments()->create([
                'file_path' => $file->store('travel-expense-reports/attachments', 'public'),
                'original_name' => $file->getClientOriginalName(),
            ]);
        }
    }

    public function delete(TravelExpenseReport $report): void
    {
        DB::transaction(function () use ($report) {
            $paths = [
                ...$report->attachments->pluck('file_path')->all(),
                ...($report->proof_of_payment_path ? [$report->proof_of_payment_path] : []),
            ];

            $report->delete();

            foreach ($paths as $path) {
                Storage::disk('public')->delete($path);
            }
        });
    }

    // Format sama seperti Annual Ticket / Travel Order: <PREFIX>-<Ym>-<0001>, nomor urut per bulan
    public function generateDocumentNumber(): string
    {
        $prefix = 'TER-'.now()->format('Ym').'-';
        $last = TravelExpenseReport::where('document_number', 'like', $prefix.'%')->orderByDesc('document_number')->value('document_number');
        $next = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
