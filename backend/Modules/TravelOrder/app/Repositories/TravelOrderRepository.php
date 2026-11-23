<?php

namespace Modules\TravelOrder\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\MasterManagement\Models\MasterDivision;
use Modules\TravelOrder\Models\TravelOrder;

class TravelOrderRepository
{
    public function paginate(
        array $filters = [],
        int $perPage = 10
    ): LengthAwarePaginator {
        return TravelOrder::with([
            'user:id,name,email',
            'department:id,name',
            'advance',
            'currentApproval.role:id,name',
        ])
            
->when(
    $filters['manager_id'] ?? null,
    function ($query, $managerId) use ($filters) {
        $departmentId = $filters['department_id'] ?? null;

        $query->where(function ($q) use ($managerId, $departmentId) {
            $q->where('user_id', $managerId);

            if ($departmentId) {
                $q->orWhere(function ($staffQuery) use ($departmentId) {
                    $staffQuery
                        ->where('department_id', $departmentId)
                        ->whereHas('approvals', function ($approvalQuery) {
                            $approvalQuery
                                ->where('status', 'pending')
                                // Hanya tahap yang sedang berjalan - step pending lebih awal tidak dihitung
                                ->whereNotExists(function ($sub) {
                                    $sub->select(DB::raw(1))
                                        ->from('travel_order_approvals as prev2')
                                        ->whereColumn('prev2.travel_order_id', 'travel_order_approvals.travel_order_id')
                                        ->where('prev2.status', 'pending')
                                        ->whereColumn('prev2.sequence', '<', 'travel_order_approvals.sequence');
                                })
                                ->whereHas('role', function ($roleQuery) {
                                    $roleQuery->where('name', 'Dept Head');
                                });
                        });
                });
            }
        });
    }
)
            ->when(
                $filters['requester_or_div_head_id'] ?? null,
                function ($query, $userId) {
                    $divisionIds = MasterDivision::where('division_head_id', $userId)
                        ->pluck('id');

                    $query->where(function ($q) use ($userId, $divisionIds) {
                        $q->where('user_id', $userId)
                            ->orWhere(function ($approvalQuery) use ($divisionIds) {
                                $approvalQuery
                                    ->where('status', 'awaiting_approval')
                                    ->whereHas('approvals', function ($approval) use ($divisionIds) {
                                        $approval
                                            ->where('status', 'pending')
                                            // Hanya tahap yang sedang berjalan - step pending lebih awal tidak dihitung
                                            ->whereNotExists(function ($sub) {
                                                $sub->select(DB::raw(1))
                                                    ->from('travel_order_approvals as prev')
                                                    ->whereColumn(
                                                        'prev.travel_order_id',
                                                        'travel_order_approvals.travel_order_id'
                                                    )
                                                    ->where('prev.status', 'pending')
                                                    ->whereColumn(
                                                        'prev.sequence',
                                                        '<',
                                                        'travel_order_approvals.sequence'
                                                    );
                                            })
                                            ->where(function ($match) use ($divisionIds) {
                                                // Step matrix spesifik divisi -> division_id step milik divisi user
                                                $match->whereHas('matrixStep', function ($step) use ($divisionIds) {
                                                    $step->whereIn('division_id', $divisionIds);
                                                })->orWhere(function ($legacy) use ($divisionIds) {
                                                    $legacy
                                                        ->whereHas('role', function ($role) {
                                                            $role->where('name', 'Div Head');
                                                        })
                                                        ->whereHas('travelOrder', function ($order) use ($divisionIds) {
                                                            $order->whereHas('department', function ($department) use ($divisionIds) {
                                                                $department->whereIn('division_head_id', $divisionIds);
                                                            });
                                                        });
                                                });
                                            });
                                    });
                            });
                    });
                }
            )
            ->when(
                $filters['user_id'] ?? null,
                fn ($query, $userId) => $query->where('user_id', $userId)
            )
            ->when(
                ($filters['department_id'] ?? null) && !($filters['manager_id'] ?? null),
                fn ($query, $departmentId) => $query->where('department_id', $departmentId)
            )
            ->when(
                $filters['exclude_draft'] ?? null,
                fn ($query) => $query->where('status', '!=', 'draft')
            )
            
            ->when(
        $filters['gmo'] ?? false,
    fn ($query) => $query->whereIn('status', [
                'awaiting_approval_gmo',
                'awaiting_gmo_processing',
                'awaiting_gmo_booking_document_issuance',
                'processed_by_gmo',
            ])
            )
            ->when(
                $filters['gmo_status'] ?? null,
                fn ($query, $statuses) => $query->where(function ($q) use ($statuses, $filters) {
                    $q->whereIn('status', $statuses);

                    if ($filters['gmo_user_id'] ?? null) {
                        $q->orWhere('user_id', $filters['gmo_user_id']);
                    }
                })
            )
            ->when(
                $filters['search'] ?? null,
                function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('order_number', 'like', "%{$search}%")
                            ->orWhere('travel_from', 'like', "%{$search}%")
                            ->orWhere('travel_to', 'like', "%{$search}%");
                    });
                }
            )
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $filters['travel_region'] ?? null,
                fn ($query, $region) => $query->where('travel_region', $region)
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
                                    ->whereColumn(
                                        'prev.travel_order_id',
                                        'travel_order_approvals.travel_order_id'
                                    )
                                    ->where('prev.status', 'pending')
                                    ->whereColumn(
                                        'prev.sequence',
                                        '<',
                                        'travel_order_approvals.sequence'
                                    );
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
            'user:id,name,email',
            'department:id,name,division_head_id',
            'department.divisionHead',
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
