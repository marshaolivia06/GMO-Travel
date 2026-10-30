<?php

namespace Modules\AnnualTicket\Services;

use Illuminate\Support\Facades\DB;
use Modules\AuthorityMatrix\Models\AuthorityMatrix;
use Modules\AnnualTicket\Models\AnnualTicket;
use Modules\UserManagement\Models\User;
use Spatie\Activitylog\Models\Activity;

class AnnualTicketApprovalService
{
    private const DOCUMENT_TYPE = 'Annual Ticket';

    public function initFlow(AnnualTicket $ticket): void
    {
        $submitter = User::with('roles')->find($ticket->employee_id);
        $submitterRole = $submitter?->roles->first();

        if (!$submitterRole) {
            throw new \RuntimeException('The applicant does not yet have a role.');
        }

        $roleIds = $submitter->roles->pluck('id');

        $matrix = AuthorityMatrix::with('steps.role')
            ->where('document_type', self::DOCUMENT_TYPE)
            ->where('status', 1)
            ->where(function ($query) use ($roleIds) {
                $query->whereNull('requester_role_id')->orWhereIn('requester_role_id', $roleIds);
            })
            // matrix spesifik requester role diprioritaskan di atas matrix generik
            ->orderByRaw('requester_role_id IS NULL')
            ->first();

        if (!$matrix || $matrix->steps->isEmpty()) {
            throw new \RuntimeException('The "Annual Ticket" authority matrix has not been created or does not yet have any steps defined.');
        }

        $stages = $matrix->steps->sortBy('step')->values();

        DB::transaction(function () use ($ticket, $submitter, $submitterRole, $stages) {
            // Riwayat putaran sebelumnya dipertahankan, putaran baru lanjut dari sequence terakhir
            $sequence = ($ticket->approvals()->max('sequence') ?? 0) + 1;

            $ticket->approvals()->create(['sequence' => $sequence++, 'role_id' => $submitterRole->id, 'user_id' => $ticket->employee_id, 'status' => 'submitted', 'action_type' => null, 'acted_at' => now()]);

            foreach ($stages as $stage) {
                $ticket->approvals()->create([
                    'sequence' => $sequence++,
                    'authority_matrix_step_id' => $stage->id,
                    'action_type' => $stage->action_type,
                    'role_id' => $stage->role_id,
                    'status' => 'pending',
                ]);
            }

            $ticket->update(['status' => $this->statusForStage($stages->first())]);
        });

        $this->logActivity($ticket, $submitter, 'submitted', 'Annual Ticket submitted', ['role' => $submitterRole->name]);
    }

    private function statusForStage($stage): string
    {
        // Normalisasi nama role: "HR - Admin" maupun "HR Admin" sama-sama menjadi hr_admin
        $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($stage->role->name)));

        // action_type 'approve' → kata statusnya 'approval' (Awaiting Approval - <Role>)
        $action = $stage->action_type === 'approve' ? 'approval' : $stage->action_type;

        return 'awaiting_' . $action . '_' . trim($slug, '_');
    }

    public function currentStage(AnnualTicket $ticket)
    {
        if (!str_starts_with($ticket->status, 'awaiting_')) {
            return null;
        }

        return $ticket->approvals()->with('role')->where('status', 'pending')->orderBy('sequence')->first();
    }

    public function currentRound(AnnualTicket $ticket): int
    {
        return Activity::where('log_name', 'annual-ticket')->where('subject_type', $ticket->getMorphClass())->where('subject_id', $ticket->id)->where('event', 'submitted')->count();
    }

    public function logActivity(AnnualTicket $ticket, ?User $causer, string $event, string $description, array $props = []): void
    {
        // submit membuka round baru, jadi dihitung +1 sebelum lognya dibuat
        $round = max(1, $this->currentRound($ticket) + ($event === 'submitted' ? 1 : 0));

        activity('annual-ticket')->performedOn($ticket)->causedBy($causer)->event($event)->withProperties(array_merge($props, ['round' => $round]))->log($description);
    }

    public function canProcess(AnnualTicket $ticket, User $user): bool
    {
        $stage = $this->currentStage($ticket);

        return $stage && $user->hasRole($stage->role->name);
    }

    public function check(AnnualTicket $ticket, User $user, ?string $note = null): void
    {
        $this->advance($ticket, $user, 'check', 'checked', 'Annual Ticket checked', $note);
    }

    public function approve(AnnualTicket $ticket, User $user, ?string $note = null): void
    {
        $this->advance($ticket, $user, 'approve', 'approved', 'Annual Ticket approved', $note);
    }

    private function advance(AnnualTicket $ticket, User $user, string $expectedAction, string $status, string $description, ?string $note = null): void
    {
        $stage = $this->currentStage($ticket);

        abort_if(!$stage, 422, 'The Annual Ticket cannot be processed at the current stage.');
        abort_if($stage->action_type !== $expectedAction, 422, 'The current stage requires a "' . $expectedAction . '" action.');
        abort_unless($this->canProcess($ticket, $user), 403, 'You are not allowed to process this Annual Ticket.');

        DB::transaction(function () use ($ticket, $user, $stage, $status, $description, $note) {
            $stage->update(['status' => $status, 'user_id' => $user->id, 'note' => $note, 'acted_at' => now()]);

            $next = $ticket->approvals()->with('role')->where('status', 'pending')->orderBy('sequence')->first();

            $this->logActivity($ticket, $user, $status, $description, ['sequence' => $stage->sequence, 'role' => $stage->role->name, 'note' => $note, 'final' => !$next]);

            if ($next) {
                $ticket->update(['status' => $this->statusForStage($next)]);

                return;
            }

            // Semua step matrix selesai → lanjut ke GMO, status tergantung ticket_arrangement
            $ticket->update(['status' => $ticket->ticket_arrangement === 'Booked by Company' ? 'awaiting_ticket_processing' : 'awaiting_fare_reference_gmo']);
        });
    }

    public function revision(AnnualTicket $ticket, User $user, string $remark): void
    {
        $remark = trim($remark);
        $stage = $this->currentStage($ticket);

        abort_if(!$stage, 422, 'The Annual Ticket cannot be processed at the current stage.');
        abort_unless($this->canProcess($ticket, $user), 403, 'You are not allowed to process this Annual Ticket.');
        abort_if($remark === '', 422, 'Remark is required.');

        DB::transaction(function () use ($ticket, $user, $stage, $remark) {
            $stage->update(['status' => 'revision', 'user_id' => $user->id, 'note' => $remark, 'acted_at' => now()]);

            $this->logActivity($ticket, $user, 'revision', 'Annual Ticket returned for revision', ['sequence' => $stage->sequence, 'role' => $stage->role->name, 'remark' => $remark]);

            $ticket->approvals()->where('status', 'pending')->update(['status' => 'cancelled']);
            $ticket->update(['status' => 'revision_required']);
        });
    }

    public function latestRoundApprovals(AnnualTicket $ticket)
    {
        $approvals = $ticket->approvals()->with('role', 'user')->get();
        $lastSubmitted = $approvals->where('status', 'submitted')->max('sequence');

        return $lastSubmitted === null ? $approvals : $approvals->where('sequence', '>=', $lastSubmitted)->values();
    }
}
