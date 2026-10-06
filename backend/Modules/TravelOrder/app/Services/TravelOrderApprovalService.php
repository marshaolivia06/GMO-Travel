<?php

namespace Modules\TravelOrder\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\AuthorityMatrix\Models\AuthorityMatrix;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\MasterDivision;
use Modules\TravelOrder\Emails\TravelOrderAwaitingApprovalMail;
use Modules\TravelOrder\Models\TravelOrder;
use Modules\UserManagement\Models\User;

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

        DB::transaction(function () use ($order, $submitterRole, $stages) {
            // Riwayat putaran sebelumnya dipertahankan, putaran baru lanjut dari sequence terakhir
            $sequence = ($order->approvals()->max('sequence') ?? 0) + 1;

            $order->approvals()->create([
                'sequence' => $sequence++,
                'role_id' => $submitterRole->id,
                'user_id' => $order->user_id,
                'status' => 'submitted',
                'acted_at' => now(),
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

            if (!$hasNext) {
                $order->update(['status' => 'approved']);
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

            // Tahap yang belum diproses di putaran ini dibatalkan
            $order->approvals()->where('status', 'pending')->update(['status' => 'cancelled']);

            $order->update([
                'status' => 'draft',
                'approval_remark' => $remark,
            ]);
        });
    }
}