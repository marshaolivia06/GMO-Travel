<?php

namespace Modules\TravelOrder\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Mail, Storage};
use Modules\MasterManagement\Models\{Department, MasterDivision, Section};
use Modules\TravelOrder\Emails\TravelOrderDeclinedMail;
use Modules\TravelOrder\Models\{MasterRegion, TravelOrder, TravelOrderGroup};
use Illuminate\Database\Eloquent\Collection;
use Modules\TravelOrder\Repositories\{TravelOrderAdvanceRepository, TravelOrderGroupRepository, TravelOrderRepository};
use Modules\UserManagement\Models\User;

class TravelOrderService
{
    public function __construct(
        private TravelOrderRepository $repository, private TravelOrderAdvanceRepository $advanceRepository,
        private MasterRateLimitService $rateLimitService, private TravelOrderApprovalService $approvalService,
        private TravelOrderGroupRepository $groupRepository
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
            $filters['gmo_status'] = ['awaiting_approval_gmo', 'awaiting_gmo_open_travel_advance', 'awaiting_gmo_processing', 'awaiting_gmo_booking_document_issuance', 'processed_by_gmo', 'cancelled'];
        } elseif ($isDeptHead) {
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
        } else {
            $filters['user_id'] = $user->id;
        }

        $orders = $this->repository->paginate($filters, $perPage);

        $orders->getCollection()->each(function ($order) use ($user) {
            $requester = User::find($order->created_by ?? $order->user_id);
            $roles = $requester?->getRoleNames() ?? collect();

            $requesterRole = $roles->contains('Div Head') ? 'Div Head'
                : ($roles->contains('Dept Head') ? 'Dept Head'
                : ($roles->contains('Sect Head') ? 'Sect Head' : null));

            $order->setAttribute('requester_role', $requesterRole);
            $order->setAttribute('requester_division_name', $requesterRole === 'Div Head' ? MasterDivision::where('division_head_id', $requester->id)->value('division_name') : null);
            $order->setAttribute('can_approve', $this->approvalService->canApprove($order, $user));
        });

        return $orders;
    }

    public function find(int $id, ?User $user = null): TravelOrder
    {
        $order = $this->repository->findById($id);
        $order->load(['approvals' => fn ($query) => $query->orderBy('sequence'), 'approvals.role', 'approvals.user', 'department.divisionHead', 'booking', 'emails', 'bookingCancellations.processor:id,name', 'group.members.user']);

        if ($order->group) {
            $this->groupRepository->attachDepartments(new Collection([$order->group]));
        }

        $order->setAttribute('requester_section_name', Section::where('section_head_id', $order->created_by)->value('name'));
        $order->setAttribute('requester_division_name', MasterDivision::where('division_head_id', $order->created_by)->value('division_name'));
        $order->setAttribute('can_approve', $user ? $this->approvalService->canApprove($order, $user) : false);

        return $order;
    }

    public function pdf(int $id, ?User $user = null)
    {
        $order = $this->find($id, $user);
        $order->setRelation('approvals', $this->latestRound($order->approvals));

        return Pdf::loadView('travelorder::pdf.travel_order', ['order' => $order])->setPaper('a4');
    }

    private function latestRound($approvals)
    {
        $lastSubmitted = $approvals->where('status', 'submitted')->max('sequence');

        return $lastSubmitted === null ? $approvals : $approvals->where('sequence', '>=', $lastSubmitted)->values();
    }

    public function history(int $id, ?User $user = null): array
    {
        return $this->approvalService->history($this->repository->findById($id));
    }

    public function approve(int $id, User $user, ?string $note = null): TravelOrder
    {
        $this->approvalService->approve($this->repository->findById($id), $user, $note);

        return $this->find($id, $user);
    }

    public function reject(int $id, User $user, string $remark): TravelOrder
    {
        $this->approvalService->reject($this->repository->findById($id), $user, $remark);

        return $this->find($id, $user);
    }

    public function revision(int $id, User $user, string $remark, ?UploadedFile $evidence = null): TravelOrder
    {
        $travelOrder = $this->repository->findById($id);

        if (in_array($travelOrder->status, ['awaiting_gmo_processing', 'awaiting_gmo_booking_document_issuance'], true)) {
            return $this->revisionGmo($id, $user, $remark, $evidence);
        }

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
            ? Department::where('id', $section->department_id)->where('status', 1)->first()
            : Department::where('status', 1)->where(fn ($query) => $query->where('dept_head_id', $user->id)->orWhere('dept_admin_id', $user->id))->first();

        if (!$department && $section) {
            $department = Department::where('status', 1)->where(fn ($query) => $query->where('dept_head_id', $user->id)->orWhere('dept_admin_id', $user->id))->first();
        }

        return $department ? ['locked' => true, 'department' => ['id' => $department->id, 'name' => $department->name]] : ['locked' => false, 'department' => null];
    }

private function getGroupTripMembers(array $memberIds): array
{
    $memberIds = array_values(array_unique(array_map('intval', $memberIds)));

    abort_if(empty($memberIds), 422, 'Select at least one member for the Group Trip.');

    $members = User::query()
        ->whereIn('id', $memberIds)
        ->where('status', 1)
        ->get()
        ->keyBy('id');

    abort_if($members->count() !== count($memberIds), 422, 'One or more members are invalid or inactive.');

    $sections = Section::query()
        ->with('department')
        ->where('status', 1)
        ->whereIn('section_head_id', $memberIds)
        ->whereHas('department', fn ($query) => $query->where('status', 1))
        ->get()
        ->unique('section_head_id')
        ->keyBy('section_head_id');

    return collect($memberIds)->map(function ($memberId) use ($members, $sections) {
        $member = $members->get($memberId);
        $roles = $member->getRoleNames();

        abort_if($roles->contains('Admin'), 422, "User ID {$memberId} with Admin role cannot be a Group Trip member.");

        if ($roles->contains('Div Head')) {
            return ['user_id' => $memberId, 'department_id' => null];
        }

        if ($roles->contains('Sect Head')) {
            $section = $sections->get($memberId);

            abort_if(!$section, 422, "No active section or department found for user ID {$memberId}.");

            return ['user_id' => $memberId, 'department_id' => $section->department_id];
        }

        if ($roles->contains('Dept Head')) {
            $department = Department::query()
                ->where('status', 1)
                ->where('dept_head_id', $memberId)
                ->first();

            abort_if(!$department, 422, "No active department found for user ID {$memberId}.");

            return ['user_id' => $memberId, 'department_id' => $department->id];
        }

        $department = Department::query()
            ->where('status', 1)
            ->where(fn ($query) => $query
                ->where('dept_admin_id', $memberId)
                ->orWhere('dept_head_id', $memberId))
            ->first();

        if (!$department) {
            $section = Section::query()
                ->with('department')
                ->where('section_head_id', $memberId)
                ->where('status', 1)
                ->whereHas('department', fn ($query) => $query->where('status', 1))
                ->first();

            $department = $section?->department;
        }

        return [
            'user_id' => $memberId,
            'department_id' => $department?->id,
        ];
    })->all();
}
private function assertGroupMember(array $data, User $user): void
{
    $groupId = $data['travel_order_group_id'] ?? null;

    abort_if(!$groupId, 422, 'Group Trip is required.');

    $group = TravelOrderGroup::query()->whereKey($groupId)->where('status', 'open')->first();

    abort_if(!$group, 422, 'Group Trip not found or inactive.');
    abort_if(!$group->members()->where('user_id', $user->id)->exists(), 403, 'You are not a member of this Group Trip.');
}
private function getGroupTripMemberIds(array $data): array
{
    $groupId = $data['travel_order_group_id'] ?? null;

    abort_if(!$groupId, 422, 'Group Trip is required.');

    $group = TravelOrderGroup::query()
        ->whereKey($groupId)
        ->where('status', 'open')
        ->first();

    abort_if(!$group, 422, 'Group Trip not found or inactive.');

    $memberIds = $group->members()
        ->pluck('user_id')
        ->unique()
        ->values()
        ->all();

    return $memberIds;
}

    
public function create(array $data, User $user): TravelOrder
{
    $tripType = $data['trip_type'] ?? 'individual';
    $isGroupTrip = $tripType === 'group';

    if ($isGroupTrip) $this->assertGroupMember($data, $user);

    $lockInfo = $this->getDepartmentLockInfo($user);
    $isDivHead = $user->getRoleNames()->contains('Div Head');

    $departmentId = $lockInfo['locked'] ? $lockInfo['department']['id'] : ($isDivHead ? null : ($data['department_id'] ?? null));

    $data = $this->applyRegion($data);

    $status = $data['status'] ?? 'draft';
    $isDomestic = ($data['travel_region'] ?? '') === 'Domestic';
    $isAdvanceOpenRequest = $status === 'awaiting_gmo_open_travel_advance';

    abort_if($isAdvanceOpenRequest && !$isDomestic, 422, 'Travel Advance open request is only available for Domestic trips.');

    $data['advance_status'] = $isDomestic ? 'locked' : 'open';

    if ($status === 'submitted') {
        $days = max(1, (int) Carbon::parse($data['departure_date'])->startOfDay()->diffInDays(Carbon::parse($data['return_date'])->startOfDay(), true) + 1);

        $validation = $this->rateLimitService->validateAdvance((int) ($data['region_id'] ?? 0), $data['travel_to'] ?? null, (float) ($data['pocket_money'] ?? 0), (float) ($data['meal_allowance'] ?? 0), (int) ($user->employee?->grade ?? 0), $days, $data['departure_date']);

        abort_if(!$validation['valid'], 422, $validation['message']);
    }

    return DB::transaction(function () use ($data, $user, $departmentId, $tripType, $isAdvanceOpenRequest) {
        $data['order_number'] = $this->generateOrderNumber();
        $data['trip_type'] = $tripType;
        $data['user_id'] = $user->id;
        $data['department_id'] = $departmentId;
        $data['created_by'] = $user->id;

        $advanceData = [
            'meal_allowance' => $data['meal_allowance'] ?? null,
            'pocket_money' => $data['pocket_money'] ?? null,
            'currency' => $data['currency'] ?? null,
        ];

        unset($data['meal_allowance'], $data['pocket_money'], $data['currency'], $data['members']);

        $travelOrder = $this->repository->create($data);

        $advanceData['travel_order_id'] = $travelOrder->id;
        $this->advanceRepository->create($advanceData);

        if ($isAdvanceOpenRequest) {
            $this->approvalService->logGmoActivity($travelOrder, $user, 'advance_open_requested', 'Travel Advance open request sent to Admin-GMO', ['role' => $user->getRoleNames()->first()]);
        }

        if (($data['status'] ?? 'draft') === 'submitted') {
            $this->approvalService->initFlow($travelOrder);
        }

        return $travelOrder->fresh()->load('group.members.user');
    });
}

    public function update(int $id, array $data, User $user): TravelOrder
    {
        $travelOrder = $this->repository->findById($id);

        abort_if($travelOrder->user_id !== $user->id, 403, 'You do not have access to modify this Travel Order.');

        $from = $travelOrder->status;
        $isAdvanceFlow = in_array($from, ['awaiting_input_travel_advance', 'awaiting_submit_without_travel_advance'], true);
        $isEditablePlain = in_array($from, ['draft', 'revision_required'], true);
        $allowedTargets = $isAdvanceFlow ? ['submitted', $from] : ['submitted', $from, 'awaiting_gmo_open_travel_advance'];

        abort_if(!$isAdvanceFlow && !$isEditablePlain, 422, 'Only Travel Orders with draft, revision required, or awaiting travel advance input status can be modified.');

        $status = $data['status'] ?? $from;

        abort_if(!in_array($status, $allowedTargets, true), 422, 'The Travel Order status transition is not allowed.');

        $tripType = $data['trip_type'] ?? $travelOrder->trip_type ?? 'individual';
$isGroupTrip = $tripType === 'group';

if ($isGroupTrip) $this->assertGroupMember($data, $user);

$lockInfo = $this->getDepartmentLockInfo($user);
$isDivHead = $user->getRoleNames()->contains('Div Head');

$departmentId = $lockInfo['locked'] ? $lockInfo['department']['id'] : ($isDivHead ? null : ($data['department_id'] ?? $travelOrder->department_id ?? null));

$data = $this->applyRegion($data);

$isDomestic = ($data['travel_region'] ?? $travelOrder->travel_region) === 'Domestic';

if ($isAdvanceFlow) {
    abort_if(!$isDomestic, 422, 'Travel region cannot be changed during the travel advance process.');
    abort_if(isset($data['region_id']) && (int) $data['region_id'] !== (int) $travelOrder->region_id, 422, 'Travel region cannot be changed during the travel advance process.');

    unset($data['advance_status']);

    if ($from === 'awaiting_submit_without_travel_advance') $data['meal_allowance'] = $data['pocket_money'] = $data['currency'] = null;
} elseif ($from === 'draft') {
    if ($status === 'awaiting_gmo_open_travel_advance') abort_if(!$isDomestic, 422, 'Travel Advance open request is only available for Domestic trips.');

    $data['advance_status'] = $isDomestic ? 'locked' : 'open';
} elseif ($from === 'revision_required') {
    if ($status === 'awaiting_gmo_open_travel_advance') abort_if(!$isDomestic, 422, 'Travel Advance open request is only available for Domestic trips.');

    $regionChanged = isset($data['region_id']) && (int) $data['region_id'] !== (int) $travelOrder->region_id;

    if ($regionChanged) $data['advance_status'] = $isDomestic ? 'locked' : 'open';
    else unset($data['advance_status']);
} else {
    unset($data['advance_status']);
}

        if ($status === 'submitted') {
            $days = max(1, (int) Carbon::parse($data['departure_date'])->startOfDay()->diffInDays(Carbon::parse($data['return_date'])->startOfDay(), true) + 1);

            $validation = $this->rateLimitService->validateAdvance((int) ($data['region_id'] ?? 0), $data['travel_to'] ?? null, (float) ($data['pocket_money'] ?? 0), (float) ($data['meal_allowance'] ?? 0), (int) ($user->employee?->grade ?? 0), $days, $data['departure_date']);

            abort_if(!$validation['valid'], 422, $validation['message']);
        }

        return DB::transaction(function () use ($travelOrder, $data, $departmentId, $tripType, $status) {
            $advanceData = ['meal_allowance' => $data['meal_allowance'] ?? null, 'pocket_money' => $data['pocket_money'] ?? null, 'currency' => $data['currency'] ?? null];

            unset($data['meal_allowance'], $data['pocket_money'], $data['currency'], $data['members'], $data['user_id'], $data['created_by'], $data['order_number']);

            $data['trip_type'] = $tripType;
            $data['department_id'] = $departmentId;

            $travelOrder->update($data);

            $advance = $this->advanceRepository->findByTravelOrderId($travelOrder->id);

            if ($advance) {
                $advance->update($advanceData);
            } else {
                $advanceData['travel_order_id'] = $travelOrder->id;
                $this->advanceRepository->create($advanceData);
            }

            if ($status === 'awaiting_gmo_open_travel_advance') {
                $this->approvalService->logGmoActivity($travelOrder, $user, 'advance_open_requested', 'Travel Advance open request sent to Admin-GMO', ['role' => $user->getRoleNames()->first()]);
            }

            if ($status === 'submitted') {
                $this->approvalService->initFlow($travelOrder);
            }

            return $travelOrder->fresh()->load('group.members.user');
        });
    }

    public function decideAdvanceOpen(int $id, User $user, bool $approved): TravelOrder
    {
        $travelOrder = $this->repository->findById($id);

        abort_unless($user->hasRole('Admin-GMO'), 403, 'You are not authorized to process this request.');
        abort_if($travelOrder->status !== 'awaiting_gmo_open_travel_advance', 422, 'This Travel Advance open request cannot be processed.');

        $travelOrder->update($approved
            ? ['advance_status' => 'open', 'status' => 'awaiting_input_travel_advance']
            : ['advance_status' => 'declined', 'status' => 'awaiting_submit_without_travel_advance']);

        $this->approvalService->logGmoActivity(
            $travelOrder,
            $user,
            $approved ? 'advance_opened' : 'advance_declined',
            $approved ? 'Travel Advance open request approved by Admin-GMO' : 'Travel Advance open request declined by Admin-GMO',
            ['role' => 'Admin-GMO']
        );

        return $this->find($id, $user);
    }

    private function applyRegion(array $data): array
{
    if (empty($data['region_id'])) return $data;

    $region = MasterRegion::findOrFail($data['region_id']);
    $data['travel_region'] = match ($region->code) { 'DN' => 'Domestic', 'LN-SG' => 'Singapore', default => 'Overseas' };
    $data['currency'] = $region->currency;

    return $data;
}

    private function generateOrderNumber(): string
    {
        $prefix = 'TO-' . now()->format('Ym') . '-';
        $last = $this->repository->lastOrderNumberWithPrefix($prefix);
        $next = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function cancel(int $id, User $user, string $remark, UploadedFile $evidence): TravelOrder
    {
        return $this->handleGmoReturnToDraft($id, $user, $remark, $evidence, 'cancelled');
    }

    public function revisionGmo(int $id, User $user, string $remark, ?UploadedFile $evidence = null): TravelOrder
    {
        return $this->handleGmoReturnToDraft($id, $user, $remark, $evidence, 'revision');
    }

    private function handleGmoReturnToDraft(int $id, User $user, string $remark, ?UploadedFile $evidence, string $action): TravelOrder
    {
        $travelOrder = $this->repository->findById($id);
        $remark = trim($remark);

        abort_unless($user->hasRole('Admin-GMO'), 403, 'You are not authorized to process this Travel Order.');
        abort_if(!in_array($travelOrder->status, ['awaiting_gmo_processing', 'awaiting_gmo_booking_document_issuance'], true), 422, 'The Travel Order cannot be processed in its current status.');
        abort_if($remark === '', 422, 'Remark is required.');

        $evidencePath = $evidence?->store('travel-order/gmo-evidence', 'public');

        try {
            DB::transaction(function () use ($travelOrder, $user, $remark, $evidencePath, $action) {
                $travelOrder->update(['status' => $action === 'revision' ? 'revision_required' : 'cancelled', 'gmo_remark' => $remark, 'gmo_evidence' => $evidencePath]);

                $description = $action === 'cancelled' ? 'Travel Order cancelled by Admin-GMO' : 'Travel Order returned for revision by Admin-GMO';

                $this->approvalService->logGmoActivity($travelOrder, $user, $action, $description, ['role' => 'Admin-GMO', 'remark' => $remark, 'evidence' => $evidencePath]);

                DB::afterCommit(function () use ($travelOrder, $user, $remark, $action) {
                    $requester = User::find($travelOrder->user_id);
                    if (!$requester?->email) return;

                    $roundStart = $travelOrder->approvals()->where('status', 'submitted')->max('sequence') ?? 0;
                    $ccEmails = $travelOrder->approvals()->with('user')->where('status', 'approved')->where('sequence', '>', $roundStart)->get()
                        ->pluck('user.email')->filter()->unique()
                        ->reject(fn ($email) => strcasecmp($email, $requester->email) === 0 || ($user->email && strcasecmp($email, $user->email) === 0))
                        ->values()->all();

                    $mail = Mail::to($requester->email);
                    if ($ccEmails) $mail->cc($ccEmails);

                    $mail->queue(new TravelOrderDeclinedMail($travelOrder, $action, $user->name, 'Admin-GMO', $remark));
                });
            });
        } catch (\Throwable $e) {
            if ($evidencePath) Storage::disk('public')->delete($evidencePath);
            throw $e;
        }

        return $this->find($id, $user);
    }
}