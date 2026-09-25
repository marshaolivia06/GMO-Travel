<?php

namespace Modules\TravelOrder\Services;

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

    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function find(int $id): TravelOrder
    {
        return $this->repository->findById($id);
    }

    public function getDepartmentLockInfo(User $user): array
    {
        $department = Department::where('dept_head_id', $user->id)
            ->orWhere('dept_admin_id', $user->id)
            ->first();

        if ($department) {
            return [
                'locked' => true,
                'department' => ['id' => $department->id, 'name' => $department->name],
            ];
        }

        $section = Section::where('section_head_id', $user->id)->first();

        if ($section) {
            $department = Department::find($section->department_id);

            if ($department) {
                return [
                    'locked' => true,
                    'department' => ['id' => $department->id, 'name' => $department->name],
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

        $isOverseas = in_array($data['travel_region'], ['Singapore', 'Non Singapore']);

        if ($isOverseas) {
            $currency = $data['meal_currency'] ?? $data['pocket_currency'] ?? null;

            $validation = $this->advanceService->validateAdvance(
                $data['travel_region'],
                $currency,
                (float) ($data['pocket_money'] ?? 0),
                (float) ($data['meal_allowance'] ?? 0)
            );

            abort_if(! $validation['valid'], 422, $validation['message']);
        }

        return DB::transaction(function () use ($data, $user, $departmentId) {
            $data['order_number'] = $this->generateOrderNumber();
            $data['trip_type'] = 'individual';
            $data['status'] = 'submitted';
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

            if ($data['travel_region'] === 'Indonesia') {
                $advanceData['meal_allowance'] = null;
                $advanceData['pocket_money'] = null;
                $advanceData['currency'] = null;
            }

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
        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}