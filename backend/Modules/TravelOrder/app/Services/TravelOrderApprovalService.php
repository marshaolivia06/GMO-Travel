<?php

namespace Modules\TravelOrder\Services;

use Illuminate\Support\Facades\DB;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\MasterDivision;
use Modules\TravelOrder\Models\TravelOrder;
use Modules\UserManagement\Models\User;
use Spatie\Permission\Models\Role;

class TravelOrderApprovalService
{

    public const FLOW = [
        'Dept Head'          => 'awaiting_approval_manager',
        'Director'           => 'awaiting_approval_director',
        'President Director' => 'awaiting_approval_presdir',
    ];

    public function initFlow(TravelOrder $order): void
    {
        $roleNames = array_keys(self::FLOW);

        $roles = Role::whereIn('name', $roleNames)
            ->pluck('id', 'name');

        $missing = collect($roleNames)
            ->reject(fn ($name) => isset($roles[$name]));

        if ($missing->isNotEmpty()) {
            throw new \RuntimeException(
                'Role belum dibuat: ' . $missing->implode(', ')
            );
        }

        // Role pengaju
        $submitter = User::find($order->user_id);

        $submitterRole = $submitter?->roles()->first();

        if (! $submitterRole) {
            throw new \RuntimeException(
                'Pengaju belum memiliki role.'
            );
        }

        DB::transaction(function () use (
            $order,
            $roles,
            $submitterRole
        ) {
            // Submit ulang = alur approval dimulai dari awal
            $order->approvals()->delete();

            // Pengaju
            $order->approvals()->create([
                'sequence' => 1,
                'role_id'  => $submitterRole->id,
                'user_id'  => $order->user_id,
                'status'   => 'submitted',
                'acted_at' => now(),
            ]);

            // Tahap approval
            $sequence = 2;

            foreach (self::FLOW as $roleName => $orderStatus) {
                $order->approvals()->create([
                    'sequence' => $sequence++,
                    'role_id'  => $roles[$roleName],
                    'status'   => 'pending',
                ]);
            }

        });
    }

    private function currentStage(TravelOrder $order)
    {
        return $order->approvals()
            ->with('role')
            ->where('status', 'pending')
            ->orderBy('sequence')
            ->first();
    }


    public function canApprove(
        TravelOrder $order,
        User $user
    ): bool {
        $stage = $this->currentStage($order);

        if (! $stage) {
            return false;
        }

        if ($stage->role->name === 'Dept Head') {
            return Department::where('id', $order->department_id)
                ->where('dept_head_id', $user->id)
                ->exists();
        }

        if ($stage->role->name === 'Director') {
            $department = Department::find($order->department_id);

            if (
                ! $department ||
                ! $department->division_head_id
            ) {
                return false;
            }

            return MasterDivision::where(
                'id',
                $department->division_head_id
            )
                ->where(
                    'division_head_id',
                    $user->id
                )
                ->exists();
        }

        return $user->hasRole($stage->role->name);
    }

    public function approve(
        TravelOrder $order,
        User $user,
        ?string $note = null
    ): void {
        $stage = $this->currentStage($order);

        abort_if(
            ! $stage,
            422,
            'Travel Order tidak dapat diproses pada tahap approval saat ini.'
        );

        abort_unless(
            $this->canApprove($order, $user),
            403,
            'Anda tidak berhak menyetujui Travel Order ini.'
        );

        DB::transaction(function () use (
            $order,
            $user,
            $stage,
            $note
        ) {
            // Approval saat ini menjadi approved
            $stage->update([
                'status'   => 'approved',
                'user_id'  => $user->id,
                'note'     => $note,
                'acted_at' => now(),
            ]);

        });
    }
}