<?php

namespace Modules\TravelOrder\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\TravelOrder\Models\TravelOrder;
use Illuminate\Support\Facades\DB;

class TravelOrderRepository
{
    public function paginate(
        array $filters = [],
        int $perPage = 10
    ): LengthAwarePaginator {
        return TravelOrder::with([
            'user:id,name',
            'department:id,name',
            'advance',
        ])
            ->when(
                $filters['manager_id'] ?? null,
                function ($query, $managerId) use ($filters) {
                    $query->where(function ($q) use ($managerId, $filters) {

                        // 1. Request milik Manager sendiri
                        $q->where('user_id', $managerId)

                            ->orWhere(function ($staffQuery) use ($filters) {
                                $staffQuery
                                    ->where(
                                        'department_id',
                                        $filters['department_id']
                                    )
                                    ->whereHas('approvals', function ($approvalQuery) {
                                        $approvalQuery
                                            ->where('status', 'pending')
                                            ->whereHas('role', function ($roleQuery) {
                                                $roleQuery->where(
                                                    'name',
                                                    'Dept Head'
                                                );
                                            });
                                    });
                            });
                    });
                }
            )
            ->when(
                $filters['user_id'] ?? null,
                function ($query, $userId) {
                    $query->where('user_id', $userId);
                }
            )
            ->when(
                $filters['exclude_draft'] ?? null,
                fn ($query) => $query->where('status', '!=', 'draft')
            )
            ->when(
                $filters['search'] ?? null,
                function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where(
                            'order_number',
                            'like',
                            "%{$search}%"
                        )
                            ->orWhere(
                                'travel_from',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'travel_to',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
            )
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query->where(
                    'status',
                    $status
                )
            )
            ->when(
                $filters['travel_region'] ?? null,
                fn ($query, $region) => $query->where(
                    'travel_region',
                    $region
                )
            )
            ->when(
                $filters['approval_role'] ?? null,
                function ($query, $roleName) {
                    $query->whereHas('approvals', function ($approvalQuery) use ($roleName) {
                        $approvalQuery
                            ->where('status', 'pending')
                            ->whereHas('role', function ($roleQuery) use ($roleName) {
                                $roleQuery->where('name', $roleName);
                            })

                            ->whereNotExists(function ($sub) {
                                $sub->select(DB::raw(1))
                                    ->from('travel_order_approvals as prev')
                                    ->whereColumn('prev.travel_order_id', 'travel_order_approvals.travel_order_id')
                                    ->where('prev.status', 'pending')
                                    ->whereColumn('prev.sequence', '<', 'travel_order_approvals.sequence');
                            });
                    });
                }
            )
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): TravelOrder
    {
        return TravelOrder::with([
            'user:id,name',
            'department:id,name',
            'creator:id,name',
            'advance',
        ])->findOrFail($id);
    }

    public function create(array $data): TravelOrder
    {
        return TravelOrder::create($data);
    }

    public function lastOrderNumberWithPrefix(string $prefix): ?string
    {
        return TravelOrder::withTrashed()
            ->where('order_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('order_number')
            ->value('order_number');
    }
}