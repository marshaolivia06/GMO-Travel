<?php

namespace Modules\AnnualTicket\Services;

use Modules\UserManagement\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\Section;
use Modules\AnnualTicket\Models\AnnualTicket;
use Modules\AnnualTicket\Models\AnnualTicketFareReference;
use Modules\AnnualTicket\Models\AnnualTicketFareReferenceAttachment;
use Modules\AnnualTicket\Repositories\AnnualTicketRepository;
use Modules\AnnualTicket\Services\AnnualTicketApprovalService;
use Barryvdh\DomPDF\Facade\Pdf;

class AnnualTicketService
{
    public function __construct(
        private AnnualTicketRepository $repository,
        private AnnualTicketApprovalService $approvalService,
    ) {}

    public function listFor(User $user)
    {
        $departmentCache = [];

        return $this->repository->allVisibleFor($user)
            ->each(function (AnnualTicket $ticket) use ($user, &$departmentCache) {
                $stage = $this->approvalService->currentStage($ticket);
                $ticket->loadMissing('approvals.role');
                $ticket->setAttribute('can_process', $this->approvalService->canProcess($ticket, $user));
                $ticket->setAttribute('is_owner', $ticket->employee_id === $user->id);
                $ticket->setAttribute('can_gmo_process', $this->canGmoProcess($ticket, $user));
                $ticket->setAttribute('can_gmo_summary', $user->hasRole('Admin-GMO') && $ticket->status === 'processed_by_gmo');
                $ticket->setAttribute('stage_action', $stage?->action_type);
                $ticket->setAttribute('stage_role', $stage?->role?->name);
                $ticket->setAttribute('department_name', $this->employeeDepartmentName($ticket->employee_id, $departmentCache));
            });
    }

    public function findFor(User $user, int $id): AnnualTicket
    {
        $ticket = $this->repository->findByIdFor($user, $id);
        $stage = $this->approvalService->currentStage($ticket);
        $ticket->loadMissing('approvals.role');
        $ticket->setAttribute('can_process', $this->approvalService->canProcess($ticket, $user));
        $ticket->setAttribute('is_owner', $ticket->employee_id === $user->id);
        $ticket->setAttribute('can_gmo_process', $this->canGmoProcess($ticket, $user));
        $ticket->setAttribute('can_gmo_summary', $user->hasRole('Admin-GMO') && $ticket->status === 'processed_by_gmo');
        $ticket->setAttribute('stage_action', $stage?->action_type);
        $ticket->setAttribute('stage_role', $stage?->role?->name);
        $ticket->setAttribute('department_name', $this->employeeDepartmentName($ticket->employee_id));

        return $ticket;
    }

    // Sumber nama departemen sama seperti pada PDF Annual Ticket (section head / dept head / dept admin)
    private function employeeDepartmentName(int $employeeId, array &$cache = []): ?string
    {
        if (array_key_exists($employeeId, $cache))
            return $cache[$employeeId];

        $department = Section::with('department')->where('section_head_id', $employeeId)->first()?->department
            ?? Department::where('dept_head_id', $employeeId)->first()
            ?? Department::where('dept_admin_id', $employeeId)->first();

        return $cache[$employeeId] = $department?->name;
    }

    public function canGmoProcess(AnnualTicket $ticket, User $user): bool
    {
        return $user->hasRole('Admin-GMO')
            && $ticket->status !== 'processed_by_gmo'
            && in_array($ticket->status, AnnualTicketRepository::GMO_STATUSES, true);
    }

    public function processByGmo(User $user, int $id, ?string $note = null, array $fareReferences = [], array $attachments = []): AnnualTicket
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        abort_unless($user->hasRole('Admin-GMO'), 403, 'You are not allowed to process this Annual Ticket.');
        abort_unless($ticket->status !== 'processed_by_gmo', 422, 'The Annual Ticket has already been processed.');
        abort_unless(in_array($ticket->status, AnnualTicketRepository::GMO_STATUSES, true), 422, 'The Annual Ticket is not at the GMO processing stage.');

        DB::transaction(function () use ($ticket, $user, $note, $fareReferences, $attachments) {
            $ticket->update(['status' => 'processed_by_gmo']);

            $this->approvalService->logActivity($ticket, $user, 'processed', 'Annual Ticket processed by GMO', ['role' => 'Admin-GMO', 'note' => $note, 'final' => true]);

            foreach ($fareReferences as $reference) {
                AnnualTicketFareReference::updateOrCreate(
                    ['request_id' => $ticket->id, 'direction' => $reference['direction']],
                    [
                        'operator_name' => $reference['operator_name'],
                        'fare_amount' => $reference['fare_amount'],
                        'currency' => $reference['currency'] ?? 'IDR',
                        'created_by' => $user->id,
                    ],
                );
            }

            foreach ($attachments as $file) {
                $path = $file->store('annual-tickets/fare-references', 'public');

                AnnualTicketFareReferenceAttachment::create([
                    'request_id' => $ticket->id,
                    'file_path' => $path,
                    'created_by' => $user->id,
                ]);
            }
        });

        return $ticket->refresh()->load(['legs', 'fareReferences', 'fareReferenceAttachments']);
    }

    public function create(User $user, array $data, $evidence): AnnualTicket
    {
        $path = $evidence->store('annual-tickets/evidence', 'public');

        $ticket = $this->repository->create($user, $data, $path, $data['travelers']);

        $this->approvalService->initFlow($ticket);

        return $ticket;
    }

    public function resubmit(User $user, int $id, array $data, $evidence): AnnualTicket
    {
        $ticket = $this->repository->findByIdForEmployee($user, $id);

        abort_unless($ticket->status === 'revision_required', 422, 'Only tickets that require revision can be resubmitted.');

        $path = $evidence ? $evidence->store('annual-tickets/evidence', 'public') : null;

        $ticket = $this->repository->resubmit($ticket, $data, $path, $data['travelers']);

        $this->approvalService->initFlow($ticket);

        return $ticket;
    }

    public function check(User $user, int $id, ?string $note = null): AnnualTicket
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        $this->approvalService->check($ticket, $user, $note);

        return $ticket->refresh()->load('legs');
    }

    public function approve(User $user, int $id, ?string $note = null): AnnualTicket
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        $this->approvalService->approve($ticket, $user, $note);

        return $ticket->refresh()->load('legs');
    }

    public function revision(User $user, int $id, string $remark): AnnualTicket
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        $this->approvalService->revision($ticket, $user, $remark);

        return $ticket->refresh()->load('legs');
    }

    public function updateSchedule(User $user, int $id, array $reschedules = []): AnnualTicket
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        abort_unless($user->hasRole('Admin-GMO'), 403, 'You are not allowed to update this Annual Ticket schedule.');

        $changed = DB::transaction(function () use ($ticket, $reschedules) {
            $changed = 0;

            foreach ($reschedules as $item) {
                $leg = $ticket->legs()->whereKey($item['leg_id'])->first();

                if (! $leg)
                    continue;

                $newDate = $item['reschedule_date'] ?? null;
                $originalDate = $leg->travel_date->format('Y-m-d');

                // Tanggal sama dengan original berarti bukan reschedule — kolom original tetap menjadi acuan
                if (! $newDate || $newDate === $originalDate) {
                    if ($leg->reschedule_date)
                        $leg->update(['reschedule_date' => null]);

                    continue;
                }

                $leg->update(['reschedule_date' => $newDate]);
                $changed++;
            }

            return $changed;
        });

        $this->approvalService->logActivity($ticket, $user, 'schedule_updated', 'Travel schedule updated by GMO', ['role' => 'Admin-GMO', 'reschedules' => $changed]);

        return $ticket->refresh()->load(['legs', 'employee.eligibleDestination', 'fareReferences', 'fareReferenceAttachments']);
    }

    public function requestCancellation(User $user, int $id, string $reason): AnnualTicket
    {
        $ticket = $this->repository->findByIdForEmployee($user, $id);

        abort_unless(in_array($ticket->status, AnnualTicketRepository::CANCELLABLE_STATUSES, true), 422, 'The Annual Ticket cannot be cancelled at this stage.');

        DB::transaction(function () use ($ticket, $user, $reason) {
            $ticket->update(['status' => 'awaiting_cancellation_check_hr_admin']);

            $this->approvalService->logActivity($ticket, $user, 'cancel_requested', 'Cancellation requested by employee', ['role' => 'Employee', 'note' => $reason]);
        });

        return $ticket->refresh()->load(['legs', 'employee.eligibleDestination', 'fareReferences', 'fareReferenceAttachments']);
    }

    public function pdf(int $id, User $user)
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        $submittedLog = \Spatie\Activitylog\Models\Activity::with('causer')
            ->where('log_name', 'annual-ticket')
            ->where('subject_type', $ticket->getMorphClass())
            ->where('subject_id', $ticket->id)
            ->where('event', 'submitted')
            ->first();

        $approvals = $this->approvalService->latestRoundApprovals($ticket)
            ->map(fn ($approval) => (object) [
                'sequence' => $approval->sequence,
                'role' => $approval->role_name,
                'name' => $approval->name,
                'acted_at' => $approval->acted_at,
                'status' => $approval->status,
                'action_type' => $approval->action_type,
            ]);

        if ($submittedLog && $ticket->approvals()->doesntExist()) {
            $approvals->prepend((object) [
                'sequence' => 0,
                'role' => $submittedLog->properties->get('role'),
                'name' => $submittedLog->causer?->name,
                'acted_at' => $submittedLog->created_at,
                'status' => 'submitted',
            ]);
        }

        return Pdf::loadView('annualticket::pdf.annual_ticket', ['ticket' => $ticket, 'approvals' => $approvals])->setPaper('a4');
    }

    public function historyById(int $id, User $user): array
    {
        return $this->history($this->repository->findByIdFor($user, $id));
    }

    public function history(AnnualTicket $ticket): array
    {
        return \Spatie\Activitylog\Models\Activity::with('causer')
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
}
