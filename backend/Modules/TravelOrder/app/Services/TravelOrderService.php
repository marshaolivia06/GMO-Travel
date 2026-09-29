<?php

namespace Modules\TravelOrder\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\Section;
use Modules\TravelOrder\Models\TravelOrder;
use Modules\TravelOrder\Repositories\TravelOrderAdvanceRepository;
use Modules\TravelOrder\Repositories\TravelOrderRepository;
use Modules\UserManagement\Models\User;

class TravelOrderService
{
    public function __construct(
        private TravelOrderRepository $repository,
        private TravelOrderAdvanceRepository $advanceRepository,
        private TravelAdvanceService $advanceService
    ) {
    }

    public function list(
        User $user,
        array $filters = [],
        int $perPage = 10
    ): LengthAwarePaginator {
        $filters['user_id'] = $user->id;

        return $this->repository->paginate($filters, $perPage);
    }

    public function find(int $id): TravelOrder
    {
        return $this->repository->findById($id);
    }

    public function approve(int $id): TravelOrder
    {
        $travelOrder = $this->repository->findById($id);

        $nextStatus = match ($travelOrder->status) {
            'awaiting_approval_manager' => 'awaiting_approval_director',
            'awaiting_approval_director' => 'awaiting_approval_predir',
            'awaiting_approval_predir' => 'awaiting_approval_gmo',
            'awaiting_approval_gmo' => 'approved',
            default => null,
        };

        abort_if(
            ! $nextStatus,
            422,
            'Travel Order tidak dapat diproses pada status saat ini.'
        );

        $travelOrder->update([
            'status' => $nextStatus,
        ]);

        return $travelOrder->fresh();
    }

    public function getDepartmentLockInfo(User $user): array
    {
        $department = Department::where('dept_head_id', $user->id)
            ->orWhere('dept_admin_id', $user->id)
            ->first();

        if ($department) {
            return [
                'locked' => true,
                'department' => [
                    'id' => $department->id,
                    'name' => $department->name,
                ],
            ];
        }

        $section = Section::where('section_head_id', $user->id)->first();

        if ($section) {
            $department = Department::find($section->department_id);

            if ($department) {
                return [
                    'locked' => true,
                    'department' => [
                        'id' => $department->id,
                        'name' => $department->name,
                    ],
                ];
            }
        }

        return [
            'locked' => false,
            'department' => null,
        ];
    }

    public function create(array $data, User $user): TravelOrder
    {
        $lockInfo = $this->getDepartmentLockInfo($user);

        $departmentId = $lockInfo['locked']
            ? $lockInfo['department']['id']
            : ($data['department_id'] ?? null);

        abort_if(
            ! $departmentId,
            422,
            'Department wajib dipilih.'
        );

        // Jumlah hari perjalanan
        // Tanggal berangkat dan pulang sama-sama dihitung
        $days = max(
            1,
            (int) Carbon::parse($data['departure_date'])
                ->startOfDay()
                ->diffInDays(
                    Carbon::parse($data['return_date'])->startOfDay(),
                    true
                ) + 1
        );

        $validation = $this->advanceService->validateAdvance(
            $data['travel_region'],
            $data['meal_currency']
                ?? $data['pocket_currency']
                ?? null,
            (float) ($data['pocket_money'] ?? 0),
            (float) ($data['meal_allowance'] ?? 0),
            (int) $user->grade,
            $days
        );

        abort_if(
            ! $validation['valid'],
            422,
            $validation['message']
        );

        return DB::transaction(function () use (
            $data,
            $user,
            $departmentId
        ) {
            $data['order_number'] = $this->generateOrderNumber();
            $data['trip_type'] = 'individual';
            $data['status'] = 'awaiting_approval_manager';

            // Travel Order selalu menjadi milik user yang sedang login
            $data['user_id'] = $user->id;
            $data['department_id'] = $departmentId;
            $data['created_by'] = $user->id;

            $advanceData = [
                'meal_allowance' => $data['meal_allowance'] ?? null,
                'pocket_money' => $data['pocket_money'] ?? null,
                'currency' => $data['meal_currency']
                    ?? $data['pocket_currency']
                    ?? null,
            ];

            unset(
                $data['meal_allowance'],
                $data['meal_currency'],
                $data['pocket_money'],
                $data['pocket_currency']
            );

            $travelOrder = $this->repository->create($data);

            $advanceData['travel_order_id'] = $travelOrder->id;

            $this->advanceRepository->create($advanceData);

            return $travelOrder;
        });
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'TO-' . now()->format('Ym') . '-';

        $last = $this->repository->lastOrderNumberWithPrefix($prefix);

        $next = $last
            ? ((int) substr($last, -4)) + 1
            : 1;

        return $prefix . str_pad(
            (string) $next,
            4,
            '0',
            STR_PAD_LEFT
        );
    }
}
