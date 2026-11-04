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

    // Rantai approval mengikuti authority matrix "Annual Ticket" (dinamis, sesuai role requester)
    private function resolveMatrix(User $submitter)
    {
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

        return $matrix->steps->sortBy('step')->values();
    }

    public function initFlow(AnnualTicket $ticket): void
    {
        $submitter = User::with('roles')->find($ticket->employee_id);
        $submitterRole = $submitter?->roles->first();

        if (!$submitterRole) {
            throw new \RuntimeException('The applicant does not yet have a role.');
        }

        $stages = $this->resolveMatrix($submitter);

        DB::transaction(function () use ($ticket, $submitter, $submitterRole, $stages) {
            // Riwayat putaran sebelumnya dipertahankan, putaran baru lanjut dari sequence terakhir
            $sequence = ($ticket->approvals()->max('sequence') ?? 0) + 1;

            $ticket->approvals()->create(['sequence' => $sequence++, 'role_id' => $submitterRole->id, 'user_id' => $ticket->employee_id, 'status' => 'submitted', 'action_type' => null, 'flow_type' => 'request', 'acted_at' => now()]);

            foreach ($stages as $stage) {
                $ticket->approvals()->create([
                    'sequence' => $sequence++,
                    'authority_matrix_step_id' => $stage->id,
                    'action_type' => $stage->action_type,
                    'flow_type' => 'request',
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

        // action_type 'approve' -> kata statusnya 'approval' (Awaiting Approval - <Role>)
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
        // Tahap finalisasi pembatalan tidak punya approval row - ditentukan dari status
        if ($ticket->status === 'awaiting_cancellation_finalization_gmo') {
            return $user->hasRole('Admin-GMO');
        }

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

            // Sedang di alur pembatalan -> status berikutnya memakai prefix cancellation
            $isCancellation = str_starts_with($ticket->status, 'awaiting_cancellation_');

            $this->logActivity($ticket, $user, $status, $isCancellation ? 'Cancellation request ' . $status : $description, [
                'sequence' => $stage->sequence,
                'role' => $stage->role->name,
                'note' => $note,
                'final' => !$next,
                ...($isCancellation ? ['flow' => 'cancellation'] : []),
            ]);

            if ($next) {
                $ticket->update(['status' => $isCancellation
                    ? $this->cancellationStatusForStage($next)
                    : $this->statusForStage($next)]);

                return;
            }

            if ($isCancellation) {
                // Semua step matrix selesai -> lanjut finalisasi pembatalan oleh Admin-GMO
                $ticket->update(['status' => 'awaiting_cancellation_finalization_gmo']);

                return;
            }

            // Semua step matrix selesai -> lanjut ke GMO, status tergantung ticket_arrangement
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

    // Alur pembatalan oleh employee - rantai approval dinamis mengikuti authority matrix,
    // setelah semua step selesai dilanjutkan finalisasi oleh Admin-GMO
    public function initCancellationFlow(AnnualTicket $ticket): void
    {
        $submitter = User::with('roles')->find($ticket->employee_id);
        $stages = $this->resolveMatrix($submitter);

        DB::transaction(function () use ($ticket, $stages) {
            // Approval alur normal yang masih pending dibatalkan - digantikan alur pembatalan
            $ticket->approvals()->where('status', 'pending')->update(['status' => 'cancelled']);

            // Hapus rantai cancellation lama (sisa pengajuan sebelumnya) agar PDF tidak menumpuk
            $this->deleteOldCancellationChains($ticket);

            $sequence = ($ticket->approvals()->max('sequence') ?? 0) + 1;

            foreach ($stages as $stage) {
                $ticket->approvals()->create([
                    'sequence' => $sequence++,
                    'authority_matrix_step_id' => $stage->id,
                    'action_type' => $stage->action_type,
                    'flow_type' => 'cancellation',
                    'role_id' => $stage->role_id,
                    'status' => 'pending',
                ]);
            }

            // Simpan status sebelum pengajuan pembatalan agar bisa dikembalikan saat reject
            $ticket->update([
                'status_before_cancellation' => $ticket->status,
                'status' => $this->cancellationStatusForStage($stages->first()),
            ]);
        });
    }

    // Hapus rantai cancellation lama: jalan mundur dari sequence terakhir sampai baris alur normal
    private function deleteOldCancellationChains(AnnualTicket $ticket): void
    {
        $rows = $ticket->approvals()->orderByDesc('sequence')->get();

        $deleteIds = [];

        foreach ($rows as $row) {
            $isCancellationRow = $row->flow_type === 'cancellation'
                || (is_null($row->flow_type) && is_null($row->authority_matrix_step_id) && ! is_null($row->action_type));

            if (! $isCancellationRow) {
                break;
            }

            $deleteIds[] = $row->getKey();
        }

        if ($deleteIds) {
            $ticket->approvals()->whereIn($ticket->approvals()->getModel()->getKeyName(), $deleteIds)->delete();
        }
    }

    private function cancellationStatusForStage($stage): string
    {
        $action = $stage->action_type === 'approve' ? 'approval' : $stage->action_type;
        $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($stage->role->name)));

        return 'awaiting_cancellation_' . $action . '_' . trim($slug, '_');
    }

    // Tolak pengajuan pembatalan (tahap check oleh HR Admin / approve oleh HR Supervisor) -> tiket kembali ke status semula
    public function rejectCancellation(AnnualTicket $ticket, User $user, string $remark): void
    {
        $stage = $this->currentStage($ticket);

        abort_if(! $stage, 422, 'The Annual Ticket cannot be processed at the current stage.');
        abort_if(! in_array($stage->action_type, ['check', 'approve'], true), 422, 'Only the check or approval stage can reject the cancellation.');
        abort_unless(str_starts_with($ticket->status, 'awaiting_cancellation_'), 422, 'The Annual Ticket is not in the cancellation flow.');
        abort_unless($this->canProcess($ticket, $user), 403, 'You are not allowed to process this Annual Ticket.');
        abort_if(trim($remark) === '', 422, 'Remark is required.');

        DB::transaction(function () use ($ticket, $user, $stage, $remark) {
            $stage->update(['status' => 'rejected', 'user_id' => $user->id, 'note' => $remark, 'acted_at' => now()]);

            // Sisa tahap pembatalan dibatalkan
            $ticket->approvals()->where('status', 'pending')->update(['status' => 'cancelled']);

            // Kembalikan ke status sebelum pengajuan pembatalan
            $restored = $ticket->status_before_cancellation
                ?? ($ticket->legCancellations()->exists() ? 'partially_cancelled' : 'processed_by_gmo');

            $ticket->update(['status' => $restored, 'status_before_cancellation' => null]);

            $this->logActivity($ticket, $user, 'cancellation_rejected', 'Cancellation rejected by ' . $stage->role->name, ['sequence' => $stage->sequence, 'role' => $stage->role->name, 'remark' => $remark, 'final' => true]);
        });
    }

    // Finalisasi pembatalan oleh Admin-GMO -> status menjadi cancelled
    public function finalizeCancellation(AnnualTicket $ticket, User $user, ?string $note = null): void
    {
        abort_unless($ticket->status === 'awaiting_cancellation_finalization_gmo', 422, 'The Annual Ticket is not at the cancellation finalization stage.');
        abort_unless($user->hasRole('Admin-GMO'), 403, 'You are not allowed to process this Annual Ticket.');

        DB::transaction(function () use ($ticket, $user, $note) {
            $this->logActivity($ticket, $user, 'finalized', 'Cancellation finalized by GMO', ['role' => 'Admin-GMO', 'note' => $note, 'final' => true]);

            $ticket->update(['status' => 'cancelled']);
        });
    }

    public function latestRoundApprovals(AnnualTicket $ticket)
    {
        $approvals = $ticket->approvals()->with('role', 'user')->get();
        $lastSubmitted = $approvals->where('status', 'submitted')->max('sequence');

        return $lastSubmitted === null ? $approvals : $approvals->where('sequence', '>=', $lastSubmitted)->values();
    }
}
