<?php

namespace Modules\AnnualTicket\Services;

use Modules\UserManagement\Models\User;
use Modules\AnnualTicket\Models\AnnualTicket;
use Modules\AnnualTicket\Repositories\AnnualTicketRepository;
use Spatie\Activitylog\Models\Activity;
use Barryvdh\DomPDF\Facade\Pdf;

class AnnualTicketService
{
    public function __construct(private AnnualTicketRepository $repository) {}

    public function listFor(User $user)
    {
        return $this->repository->allForEmployee($user);
    }

    public function create(User $user, array $data, $evidence): AnnualTicket
    {
        $path = $evidence->store('annual-tickets/evidence', 'public');

        $ticket = $this->repository->create($user, $data, $path, $data['travelers']);

        $this->logActivity($ticket, $user, 'submitted', 'Annual Ticket submitted', ['role' => $user->getRoleNames()->first()]);

        return $ticket;
    }

    public function pdf(int $id, User $user)
    {
        $ticket = $this->repository->findByIdForEmployee($user, $id);

        $submittedLog = Activity::with('causer')
            ->where('log_name', 'annual-ticket')
            ->where('subject_type', $ticket->getMorphClass())
            ->where('subject_id', $ticket->id)
            ->where('event', 'submitted')
            ->first();

        $approvals = collect();

        if ($submittedLog) {
            $approvals->push((object) [
                'role' => $submittedLog->properties->get('role'),
                'name' => $submittedLog->causer?->name,
                'acted_at' => $submittedLog->created_at,
                'status' => 'submitted',
            ]);
        } else {
            $employee = $ticket->employee ?: User::find($ticket->employee_id);

            $approvals->push((object) [
                'role' => $employee?->getRoleNames()?->first(),
                'name' => $employee?->name,
                'acted_at' => $ticket->created_at,
                'status' => 'submitted',
            ]);
        }

        return Pdf::loadView('annualticket::pdf.annual_ticket', ['ticket' => $ticket, 'approvals' => $approvals])->setPaper('a4');
    }

    public function historyById(int $id, User $user): array
    {
        return $this->history($this->repository->findByIdForEmployee($user, $id));
    }

    public function history(AnnualTicket $ticket): array
    {
        return Activity::with('causer')
            ->where('log_name', 'annual-ticket')
            ->where('subject_type', $ticket->getMorphClass())
            ->where('subject_id', $ticket->id)
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
                'evidence' => $log->properties->get('evidence'),
                'causer' => $log->causer?->name,
                'created_at' => $log->created_at,
            ])
            ->values()
            ->all();
    }

    public function logActivity(AnnualTicket $ticket, ?User $causer, string $event, string $description, array $props = []): void
    {
        activity('annual-ticket')
            ->performedOn($ticket)
            ->causedBy($causer)
            ->event($event)
            ->withProperties(array_merge($props, ['round' => 1]))
            ->log($description);
    }
}
