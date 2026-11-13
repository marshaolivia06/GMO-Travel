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

    public function allForEmployee(User $user): Collection
    {
        return TravelExpenseReport::with([
            'attachments',
            'annualTicket.legs',
            'annualTicket.fareReferences',
            'annualTicket.booking',
            'annualTicket.employee.eligibleDestination',
        ])
            ->where('employee_id', $user->id)
            ->latest()
            ->get();
    }

    public function findByIdForEmployee(User $user, int $id): TravelExpenseReport
    {
        return TravelExpenseReport::with([
            'attachments',
            'annualTicket.legs',
            'annualTicket.fareReferences',
            'annualTicket.booking',
            'annualTicket.employee.eligibleDestination',
        ])
            ->where('employee_id', $user->id)
            ->findOrFail($id);
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
