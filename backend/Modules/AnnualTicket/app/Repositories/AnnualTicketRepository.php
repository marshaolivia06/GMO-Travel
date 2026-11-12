<?php

namespace Modules\AnnualTicket\Repositories;

use Modules\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AnnualTicket\Models\AnnualTicket;

class AnnualTicketRepository
{
    // Status tahapan GMO - terlihat oleh role Admin-GMO
    public const GMO_STATUSES = [
        'awaiting_fare_reference_gmo',
        'awaiting_ticket_processing',
        'processed_by_gmo',
    ];

    // Status tiket yang sudah diproses & masih bisa dikelola GMO (dokumen, jadwal, pembatalan leg, refund)
    public const GMO_MANAGEABLE_STATUSES = [
        'processed_by_gmo',
        'partially_cancelled',
    ];

    // Status yang terlihat oleh Admin-GMO di daftar
    public const GMO_VISIBLE_STATUSES = [
        ...self::GMO_STATUSES,
        'partially_cancelled',
        'awaiting_cancellation_finalization_gmo',
    ];

    // Cancellation request hanya bisa diajukan setelah tiket diproses GMO
    public const CANCELLABLE_STATUSES = [
        'processed_by_gmo',
    ];

    public function allForEmployee(User $user): Collection
    {
        return AnnualTicket::with(['legs', 'employee.eligibleDestination', 'fareReferences', 'fareReferenceAttachments', 'booking', 'bookingDocuments', 'legCancellations', 'emails'])
            ->where('employee_id', $user->id)
            ->latest()
            ->get();
    }

    public function allVisibleFor(User $user): Collection
    {
        return AnnualTicket::with(['legs', 'employee.eligibleDestination', 'fareReferences', 'fareReferenceAttachments', 'booking', 'bookingDocuments', 'legCancellations', 'emails'])
            ->when(! $user->hasRole('Admin'), fn ($query) => $query->where($this->visibilityScope($user)))
            ->latest()
            ->get();
    }

    public function findByIdFor(User $user, int $id): AnnualTicket
    {
        return AnnualTicket::with(['legs', 'employee.eligibleDestination', 'fareReferences', 'fareReferenceAttachments', 'booking', 'bookingDocuments', 'legCancellations', 'emails'])
            ->when(! $user->hasRole('Admin'), fn ($query) => $query->where($this->visibilityScope($user)))
            ->findOrFail($id);
    }

    private function visibilityScope(User $user): \Closure
    {
        return function ($query) use ($user) {
            $query->where('employee_id', $user->id)
                ->orWhereHas('approvals', function ($approvals) use ($user) {
                    $approvals->where('status', 'pending')
                        // Hanya tahap yang sedang berjalan - step pending lebih awal tidak dihitung
                        ->whereNotExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('annual_ticket_approvals as prev')
                                ->whereColumn('prev.annual_ticket_id', 'annual_ticket_approvals.annual_ticket_id')
                                ->where('prev.status', 'pending')
                                ->whereColumn('prev.sequence', '<', 'annual_ticket_approvals.sequence');
                        })
                        ->whereHas('role', function ($roles) use ($user) {
                            $roles->whereIn('name', $user->getRoleNames());
                        });
                });

            if ($user->hasRole('Admin-GMO')) {
                $query->orWhereIn('status', self::GMO_VISIBLE_STATUSES);
            }
        };
    }

    public function findByIdForEmployee(User $user, int $id): AnnualTicket
    {
        return AnnualTicket::with(['legs', 'employee.eligibleDestination', 'fareReferences', 'fareReferenceAttachments', 'booking', 'bookingDocuments', 'legCancellations', 'emails'])
            ->where('employee_id', $user->id)
            ->findOrFail($id);
    }

    public function create(User $user, array $data, string $evidencePath, array $travelers): AnnualTicket
    {
        return DB::transaction(function () use ($user, $data, $evidencePath, $travelers) {
            $ticket = AnnualTicket::create([
                'employee_id' => $user->id,
                'quota_year' => $data['quota_year'],
                'route_condition' => $data['route_condition'],
                'ticket_arrangement' => $data['ticket_arrangement'],
                'status' => 'awaiting_check_hr_admin',
                'document_number' => $this->generateDocumentNumber(),
                'request_type' => 'annual_ticket',
                'remarks' => $data['remarks'] ?? null,
                'evidence_path' => $evidencePath,
            ]);

            $this->createLegs($ticket, $travelers);

            return $ticket->load('legs');
        });
    }

    // Format sama seperti Travel Order: AT-<Ym>-<0001>, nomor urut per bulan
    private function generateDocumentNumber(): string
    {
        $prefix = 'AT-' . now()->format('Ym') . '-';
        $last = AnnualTicket::where('document_number', 'like', $prefix . '%')->orderByDesc('document_number')->value('document_number');
        $next = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function resubmit(AnnualTicket $ticket, array $data, ?string $evidencePath, array $travelers): AnnualTicket
    {
        return DB::transaction(function () use ($ticket, $data, $evidencePath, $travelers) {
            $ticket->update([
                'quota_year' => $data['quota_year'],
                'route_condition' => $data['route_condition'],
                'ticket_arrangement' => $data['ticket_arrangement'],
                'remarks' => $data['remarks'] ?? null,
                ...($evidencePath ? ['evidence_path' => $evidencePath] : []),
            ]);

            $ticket->legs()->delete();
            $this->createLegs($ticket, $travelers);

            return $ticket->load('legs');
        });
    }

    private function createLegs(AnnualTicket $ticket, array $travelers): void
    {
        foreach ($travelers as $traveler) {
            foreach (['departure', 'return'] as $direction) {
                $date = $traveler["{$direction}_date"] ?? null;

                if (! $date) {
                    continue;
                }

                $ticket->legs()->create([
                    'traveler_type' => $traveler['type'],
                    'traveler_id' => $traveler['traveler_id'] ?? null,
                    'name' => $traveler['name'],
                    'relationship' => $traveler['relationship'] ?? null,
                    'direction' => $direction,
                    'travel_date' => $date,
                    'travel_from' => $traveler["{$direction}_from"],
                    'travel_to' => $traveler["{$direction}_to"],
                ]);
            }
        }
    }
}
