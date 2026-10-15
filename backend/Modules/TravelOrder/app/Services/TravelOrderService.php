<?php

namespace Modules\TravelOrder\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\MasterDivision;
use Modules\MasterManagement\Models\Section;
use Modules\TravelOrder\Models\TravelOrder;
use Modules\TravelOrder\Repositories\TravelOrderAdvanceRepository;
use Modules\TravelOrder\Repositories\TravelOrderRepository;
use Modules\UserManagement\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TravelOrderService
{
    public function __construct(
        private TravelOrderRepository $repository,
        private TravelOrderAdvanceRepository $advanceRepository,
        private TravelAdvanceService $advanceService,
        private TravelOrderApprovalService $approvalService
    ) {}

    public function list(User $user, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $roles = $user->getRoleNames();

        $isAdmin = $roles->contains('Admin');
        $isAdminGmo = $roles->contains('Admin-GMO');
        $isDeptHead = $roles->contains('Dept Head');
        $isDivHead = $roles->contains('Div Head');
        $isPresidentDirector = $roles->contains('President Director');

        if ($isAdmin) {
            $filters['exclude_draft'] = true;
        } elseif ($isAdminGmo) {
            $filters['gmo_status'] = [
                'awaiting_approval_gmo',
                'awaiting_gmo_processing',
                'awaiting_gmo_booking_document_issuance',
                'processed_by_gmo',
            ];
        }elseif ($isDeptHead) {
            $department = Department::where('dept_head_id', $user->id)->first();

            if ($department) {
                $filters['manager_id'] = $user->id;
                $filters['department_id'] = $department->id;
            } else {
                $filters['user_id'] = $user->id;
            }
        
} elseif ($isDivHead) {
    $filters['requester_or_div_head_id'] = $user->id;
    unset($filters['user_id'], $filters['approval_role'], $filters['department_id']);
} elseif ($isPresidentDirector) {
    $filters['approval_role'] = 'President Director';
}
 else {
            $filters['user_id'] = $user->id;
        }

        
$orders = $this->repository->paginate($filters, $perPage);

$orders->getCollection()->each(function ($order) use ($user) {
    $requester = User::find($order->created_by ?? $order->user_id);
    $requesterRoles = $requester?->getRoleNames() ?? collect();

    $requesterRole = $requesterRoles->contains('Div Head')
        ? 'Div Head'
        : ($requesterRoles->contains('Dept Head')
            ? 'Dept Head'
            : ($requesterRoles->contains('Sect Head')
                ? 'Sect Head'
                : null));

    $order->setAttribute('requester_role', $requesterRole);

    $order->setAttribute(
        'requester_division_name',
        $requesterRole === 'Div Head'
            ? MasterDivision::where('division_head_id', $requester->id)
                ->value('division_name')
            : null
    );

    $order->setAttribute(
        'can_approve',
        $this->approvalService->canApprove($order, $user)
    );
});

return $orders;
    }
public function find(int $id, ?User $user = null): TravelOrder
{
    $order = $this->repository->findById($id);

    $order->load([
        'approvals' => fn ($query) => $query->orderBy('sequence'),
        'approvals.role',
        'approvals.user',
        'department.divisionHead',
        'booking',
    ]);

    $order->setAttribute(
        'requester_section_name',
        Section::where('section_head_id', $order->created_by)->value('name')
    );

    $order->setAttribute(
        'requester_division_name',
        MasterDivision::where('division_head_id', $order->created_by)
            ->value('division_name')
    );

    $order->setAttribute(
        'can_approve',
        $user ? $this->approvalService->canApprove($order, $user) : false
    );

    return $order;
}

    public function pdf(int $id, ?User $user = null)
    {
        $order = $this->find($id, $user);
        $order->setRelation('approvals', $this->latestRound($order->approvals));

        return Pdf::loadView('travelorder::pdf.travel_order', [
            'order' => $order,
        ])->setPaper('a4');
    }

    private function latestRound($approvals)
    {
        $lastSubmitted = $approvals->where('status', 'submitted')->max('sequence');

        return $lastSubmitted === null
            ? $approvals
            : $approvals->where('sequence', '>=', $lastSubmitted)->values();
    }

    public function history(int $id, ?User $user = null): array
    {
        $order = $this->repository->findById($id);

        return $this->approvalService->history($order);
    }

    public function approve(int $id, User $user, ?string $note = null): TravelOrder
    {
        $travelOrder = $this->repository->findById($id);
        $this->approvalService->approve($travelOrder, $user, $note);

        return $this->find($id, $user);
    }

    public function reject(int $id, User $user, string $remark): TravelOrder
    {
        $travelOrder = $this->repository->findById($id);
        $this->approvalService->reject($travelOrder, $user, $remark);

        return $this->find($id, $user);
    }

    public function revision(int $id, User $user, string $remark): TravelOrder
    {
        $travelOrder = $this->repository->findById($id);
        $this->approvalService->revision($travelOrder, $user, $remark);

        return $this->find($id, $user);
    }

    
public function getDepartmentLockInfo(User $user): array
{
    if ($user->getRoleNames()->contains('Div Head')) {
        return ['locked' => false, 'department' => null];
    }

    $section = Section::where('section_head_id', $user->id)->first();

    $department = $section
        ? Department::where('id', $section->department_id)
            ->where('status', 1)
            ->first()
        : Department::where('status', 1)
            ->where(function ($query) use ($user) {
                $query->where('dept_head_id', $user->id)
                    ->orWhere('dept_admin_id', $user->id);
            })
            ->first();

    if (!$department && $section) {
        $department = Department::where('status', 1)
            ->where(function ($query) use ($user) {
                $query->where('dept_head_id', $user->id)
                    ->orWhere('dept_admin_id', $user->id);
            })
            ->first();
    }

    return $department
        ? [
            'locked' => true,
            'department' => [
                'id' => $department->id,
                'name' => $department->name,
            ],
        ]
        : ['locked' => false, 'department' => null];
}

    public function create(array $data, User $user): TravelOrder
    {
        $lockInfo = $this->getDepartmentLockInfo($user);
        $isDivHead = $user->getRoleNames()->contains('Div Head');

        $departmentId = $lockInfo['locked']
            ? $lockInfo['department']['id']
            : ($isDivHead ? null : ($data['department_id'] ?? null));

        abort_if(!$departmentId && !$isDivHead, 422, 'Department wajib dipilih.');

        if (($data['status'] ?? 'draft') === 'submitted') {
            $days = max(
                1,
                (int) Carbon::parse($data['departure_date'])
                    ->startOfDay()
                    ->diffInDays(Carbon::parse($data['return_date'])->startOfDay(), true) + 1
            );

            $validation = $this->advanceService->validateAdvance(
                $data['travel_region'],
                $data['currency'] ?? null,
                (float) ($data['pocket_money'] ?? 0),
                (float) ($data['meal_allowance'] ?? 0),
                (int) $user->grade,
                $days
            );

            abort_if(!$validation['valid'], 422, $validation['message']);
        }

        return DB::transaction(function () use ($data, $user, $departmentId) {
            $data['order_number'] = $this->generateOrderNumber();
            $data['trip_type'] = 'individual';
            $data['user_id'] = $user->id;
            $data['department_id'] = $departmentId;
            $data['created_by'] = $user->id;

            $advanceData = [
                'meal_allowance' => $data['meal_allowance'] ?? null,
                'pocket_money' => $data['pocket_money'] ?? null,
                'currency' => $data['currency'] ?? null,
            ];

            unset($data['meal_allowance'], $data['pocket_money'], $data['currency']);

            $travelOrder = $this->repository->create($data);

            $advanceData['travel_order_id'] = $travelOrder->id;
            $this->advanceRepository->create($advanceData);

            if (($data['status'] ?? 'draft') === 'submitted') {
                $this->approvalService->initFlow($travelOrder);
            }

            return $travelOrder->fresh();
        });
    }

    public function update(int $id, array $data, User $user): TravelOrder
    {
        $travelOrder = $this->repository->findById($id);

        abort_if(
            $travelOrder->user_id !== $user->id,
            403,
            'Anda tidak memiliki akses untuk mengubah Travel Order ini.'
        );

        abort_if(
            $travelOrder->status !== 'draft',
            422,
            'Hanya Travel Order dengan status draft yang dapat diubah.'
        );

        $lockInfo = $this->getDepartmentLockInfo($user);
        $isDivHead = $user->getRoleNames()->contains('Div Head');

        $departmentId = $lockInfo['locked']
            ? $lockInfo['department']['id']
            : ($isDivHead ? null : ($data['department_id'] ?? $travelOrder->department_id));

        abort_if(!$departmentId && !$isDivHead, 422, 'Department wajib dipilih.');

        if (($data['status'] ?? 'draft') === 'submitted') {
            $days = max(
                1,
                (int) Carbon::parse($data['departure_date'])
                    ->startOfDay()
                    ->diffInDays(Carbon::parse($data['return_date'])->startOfDay(), true) + 1
            );

            $validation = $this->advanceService->validateAdvance(
                $data['travel_region'],
                $data['currency'] ?? null,
                (float) ($data['pocket_money'] ?? 0),
                (float) ($data['meal_allowance'] ?? 0),
                (int) $user->grade,
                $days
            );

            abort_if(!$validation['valid'], 422, $validation['message']);
        }

        return DB::transaction(function () use ($travelOrder, $data, $departmentId) {
            $advanceData = [
                'meal_allowance' => $data['meal_allowance'] ?? null,
                'pocket_money' => $data['pocket_money'] ?? null,
                'currency' => $data['currency'] ?? null,
            ];

            unset($data['meal_allowance'], $data['pocket_money'], $data['currency']);

            $data['department_id'] = $departmentId;

            unset($data['user_id'], $data['created_by'], $data['order_number'], $data['trip_type']);

            $travelOrder->update($data);

            $advance = $this->advanceRepository->findByTravelOrderId($travelOrder->id);

            if ($advance) {
                $advance->update($advanceData);
            } else {
                $advanceData['travel_order_id'] = $travelOrder->id;
                $this->advanceRepository->create($advanceData);
            }

            if (($data['status'] ?? 'draft') === 'submitted') {
                $this->approvalService->initFlow($travelOrder);
            }

            return $travelOrder->fresh();
        });
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'TO-' . now()->format('Ym') . '-';
        $last = $this->repository->lastOrderNumberWithPrefix($prefix);
        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
public function cancel(
    int $id,
    User $user,
    string $remark,
    UploadedFile $evidence
): TravelOrder {
    $travelOrder = $this->repository->findById($id);
    $remark = trim($remark);

    abort_unless(
        $user->hasRole('Admin-GMO'),
        403,
        'You are not authorized to cancel this Travel Order.'
    );

    abort_unless(
        in_array($travelOrder->status, [
            'awaiting_gmo_processing',
            'awaiting_gmo_booking_document_issuance',
        ], true),
        422,
        'The Travel Order cannot be cancelled in its current status.'
    );

    abort_if($remark === '', 422, 'Cancellation remark is required.');

    $evidencePath = $evidence->store(
        'travel-order/cancellation-evidence',
        'public'
    );

    try {
        DB::transaction(function () use (
            $travelOrder,
            $user,
            $remark,
            $evidencePath
        ) {
            $travelOrder->update([
                'status' => 'draft',
                'cancellation_remark' => $remark,
                'cancellation_evidence' => $evidencePath,
            ]);

$this->approvalService->logGmoActivity(
    $travelOrder,
    $user,
    'cancelled',
    'Travel Order cancelled: ' . $remark,
    [
        'role' => 'Admin-GMO',
        'remark' => $remark,
        'evidence' => $evidencePath,
    ]
);

        });
    } catch (\Throwable $e) {
        Storage::disk('public')->delete($evidencePath);

        throw $e;
    }

    return $this->find($id, $user);
}
}
