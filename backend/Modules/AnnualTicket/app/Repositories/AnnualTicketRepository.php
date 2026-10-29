<?php

namespace Modules\AnnualTicket\Repositories;

use Modules\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AnnualTicket\Models\AnnualTicket;

class AnnualTicketRepository
{
    public function allForEmployee(User $user): Collection
    {
        return AnnualTicket::with('legs')
            ->where('employee_id', $user->id)
            ->latest()
            ->get();
    }

    public function findByIdForEmployee(User $user, int $id): AnnualTicket
    {
        return AnnualTicket::with('legs')
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
                'remarks' => $data['remarks'] ?? null,
                'evidence_path' => $evidencePath,
            ]);

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

            return $ticket->load('legs');
        });
    }
}
