<?php

namespace Modules\TravelOrder\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\AuthorityMatrix\Models\AuthorityMatrix;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\MasterDivision;
use Modules\TravelOrder\Emails\TravelOrderAwaitingApprovalMail;
use Modules\TravelOrder\Emails\TravelOrderDeclinedMail;
use Modules\TravelOrder\Models\TravelOrder;
use Modules\UserManagement\Models\User;
use Spatie\Activitylog\Models\Activity;

class TravelOrderApprovalService
{
    public function initFlow(TravelOrder $order): void
    {
        $matrix = AuthorityMatrix::with('steps.role')
            ->where('document_type', 'Travel Order')
            ->where('status', 1)
            ->first();

        if (!$matrix || $matrix->steps->isEmpty()) {
            throw new \RuntimeException('Authority Matrix "Travel Order" belum dibuat atau belum punya step.');
        }

        $submitter = User::with('roles')->find($order->user_id);
        $submitterRole = $submitter?->roles->first();

        if (!$submitterRole) {
            throw new \RuntimeException('Pengaju belum memiliki role.');
        }

        $steps = $matrix->steps->sortBy('step')->values();
        $roleIds = $submitter->roles->pluck('id');
        $ownStep = $steps->filter(fn($s) => $roleIds->contains($s->role_id))->max('step') ?? 0;
        $stages = $steps->where('step', '>', $ownStep)->values();

        if ($stages->isEmpty()) {
            throw new \RuntimeException('Tidak ada tahap approval setelah role pengaju.');
        }

        DB::transaction(function () use ($order, $submitter, $submitterRole, $stages) {
            // Riwayat putaran sebelumnya dipertahankan, putaran baru lanjut dari sequence terakhir
            $sequence = ($order->approvals()->max('sequence') ?? 0) + 1;

            $order->approvals()->create([
                'sequence' => $sequence++,
                'role_id' => $submitterRole->id,
                'user_id' => $order->user_id,
                'status' => 'submitted',
                'acted_at' => now(),
            ]);

            $this->logActivity($order, $submitter, 'submitted', 'Travel Order submitted', [
                'role' => $submitterRole->name,
            ]);

            foreach ($stages as $stage) {
                $order->approvals()->create([
                    'sequence' => $sequence++,
                    'role_id' => $stage->role_id,
                    'status' => 'pending',
                ]);
            }

            $order->update(['status' => 'awaiting_approval', 'approval_remark' => null]);
            DB::afterCommit(fn() => $this->notifyCurrentApprovers($order));
        });
    }

    private function currentStage(TravelOrder $order)
    {
        if ($order->status !== 'awaiting_approval') {
            return null;
        }

        return $order->approvals()
            ->with('role')
            ->where('status', 'pending')
            ->orderBy('sequence')
            ->first();
    }

    private function currentRound(TravelOrder $order): int
{
    return Activity::where('log_name', 'travel-order')
        ->where('subject_type', $order->getMorphClass())
        ->where('subject_id', $order->id)
        ->where('event', 'submitted')
        ->count();
}

private function logActivity(TravelOrder $order, ?User $causer, string $event, string $description, array $props = []): void
{
    // submit membuka round baru, jadi dihitung +1 sebelum lognya dibuat
    $round = max(1, $this->currentRound($order) + ($event === 'submitted' ? 1 : 0));

    activity('travel-order')
        ->performedOn($order)
        ->causedBy($causer)
        ->event($event)
        ->withProperties(array_merge($props, ['round' => $round]))
        ->log($description);
}
    public function notifyCurrentApprovers(TravelOrder $order): void
    {
        $stage = $this->currentStage($order);

        if (!$stage) {
            return;
        }

        $order->loadMissing('user');

        foreach ($this->approversOf($order, $stage) as $approver) {
            if ($approver->email) {
                Mail::to($approver->email)->queue(
                    new TravelOrderAwaitingApprovalMail($order, $stage->role->name)
                );
            }
        }
    }

    private function approversOf(TravelOrder $order, $stage)
    {
        $roleName = $stage->role->name;

        if ($roleName === 'Dept Head') {
            $id = Department::where('id', $order->department_id)->value('dept_head_id');
            return User::whereKey($id)->get();
        }

        if ($roleName === 'Director') {
            $department = Department::find($order->department_id);
            $division = $department?->division_head_id
                ? MasterDivision::find($department->division_head_id)
                : null;

            return User::whereKey($division?->division_head_id)->get();
        }

        return User::role($roleName)->get();
    }

    public function canApprove(TravelOrder $order, User $user): bool
    {
        $stage = $this->currentStage($order);

        if (!$stage) {
            return false;
        }

        if ($stage->role->name === 'Dept Head') {
            return Department::where('id', $order->department_id)
                ->where('dept_head_id', $user->id)
                ->exists();
        }

        if ($stage->role->name === 'Director') {
            $department = Department::find($order->department_id);

            if (!$department || !$department->division_head_id) {
                return false;
            }

            return MasterDivision::where('id', $department->division_head_id)
                ->where('division_head_id', $user->id)
                ->exists();
        }

        return $user->hasRole($stage->role->name);
    }

    public function approve(TravelOrder $order, User $user, ?string $note = null): void
    {
        $stage = $this->currentStage($order);

        abort_if(!$stage, 422, 'Travel Order tidak dapat diproses pada tahap approval saat ini.');
        abort_unless($this->canApprove($order, $user), 403, 'Anda tidak berhak menyetujui Travel Order ini.');

        DB::transaction(function () use ($order, $user, $stage, $note) {
            $stage->update([
                'status' => 'approved',
                'user_id' => $user->id,
                'note' => $note,
                'acted_at' => now(),
            ]);

            $hasNext = $order->approvals()->where('status', 'pending')->exists();

            $this->logActivity($order, $user, 'approved', 'Travel Order approved', [
                'sequence' => $stage->sequence,
                'role'     => $stage->role->name,
                'note'     => $note,
                'final'    => !$hasNext,
            ]);

            if (!$hasNext) {
                $ferry = $order->ferry_arrangement;
                $accommodation = $order->accommodation_arrangement;
            
                if (
                    $ferry === 'Direct Payment' &&
                    $accommodation === 'Direct Payment'
                ) {
                    $nextStatus = 'awaiting_gmo_processing';
                } else {
                    $nextStatus = 'awaiting_gmo_booking_preparation';
                }
            
                $order->update([
                    'status' => $nextStatus,
                ]);
            
                return;
            }

            DB::afterCommit(fn() => $this->notifyCurrentApprovers($order));
        });
    }

    public function reject(TravelOrder $order, User $user, string $remark): void
    {
        $this->decline($order, $user, $remark, 'rejected');
    }

    public function revision(TravelOrder $order, User $user, string $remark): void
    {
        $this->decline($order, $user, $remark, 'revision');
    }

    private function decline(TravelOrder $order, User $user, string $remark, string $status): void
    {
        $remark = trim($remark);

        $stage = $this->currentStage($order);

        abort_if(!$stage, 422, 'Travel Order tidak dapat diproses pada tahap approval saat ini.');
        abort_unless($this->canApprove($order, $user), 403, 'Anda tidak berhak memproses Travel Order ini.');
        abort_if($remark === '', 422, 'Remark wajib diisi.');

        DB::transaction(function () use ($order, $user, $stage, $remark, $status) {
            $stage->update([
                'status' => $status,
                'user_id' => $user->id,
                'note' => $remark,
                'acted_at' => now(),
            ]);

            $this->logActivity($order, $user, $status, "Travel Order {$status}", [
                'sequence' => $stage->sequence,
                'role'     => $stage->role->name,
                'remark'   => $remark,
            ]);

            $order->approvals()->where('status', 'pending')->update(['status' => 'cancelled']);

            $order->update([
                'status' => 'draft',
                'approval_remark' => $remark,
            ]);

            DB::afterCommit(fn() => $this->notifyRequesterDeclined($order, $user, $stage, $status, $remark));
        });
    }

    private function notifyRequesterDeclined(TravelOrder $order, User $actor, $stage, string $status, string $remark): void
    {
        // Ambil pengaju langsung dari DB, karena relasi user di $order bisa hanya memuat sebagian kolom
        $requester = User::find($order->user_id);

        if (!$requester?->email) {
            return;
        }

        // Approver yang sudah approve di putaran ini ikut diberi tahu (CC)
        $roundStart = $order->approvals()->where('status', 'submitted')->max('sequence') ?? 0;

        $ccEmails = $order->approvals()
            ->with('user')
            ->where('status', 'approved')
            ->where('sequence', '>', $roundStart)
            ->get()
            ->pluck('user.email')
            ->filter()
            ->unique()
            ->reject(fn($email) => $email === $requester->email)
            ->values()
            ->all();

        Mail::to($requester->email)
            ->cc($ccEmails)
            ->queue(new TravelOrderDeclinedMail($order, $status, $actor->name, $stage->role->name, $remark));
    }

    public function history(TravelOrder $order): array
    {
        return Activity::with('causer')
            ->where('log_name', 'travel-order')
            ->where('subject_type', $order->getMorphClass())
            ->where('subject_id', $order->id)
            ->orderBy('id')
            ->get()
            ->map(fn($log) => [
                'id'          => $log->id,
                'event'       => $log->event,
                'description' => $log->description,
                'round'       => $log->properties->get('round'),
                'sequence'    => $log->properties->get('sequence'),
                'role'        => $log->properties->get('role'),
                'note'        => $log->properties->get('note'),
                'remark'      => $log->properties->get('remark'),
                'final'       => $log->properties->get('final'),
                'causer'      => $log->causer?->name,
                'created_at'  => $log->created_at,
            ])
            ->values()
            ->all();
    }
}