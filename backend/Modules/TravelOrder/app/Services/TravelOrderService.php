<?php

namespace Modules\TravelOrder\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Mail, Storage};
use Modules\MasterManagement\Models\{Department, MasterDivision, Section};
use Modules\TravelOrder\Emails\TravelOrderDeclinedMail;
use Modules\TravelOrder\Models\TravelOrder;
use Modules\TravelOrder\Repositories\{TravelOrderAdvanceRepository, TravelOrderRepository};
use Modules\UserManagement\Models\User;

class TravelOrderService
{
    public function __construct(
        private TravelOrderRepository $repository, private TravelOrderAdvanceRepository $advanceRepository,
        private TravelAdvanceService $advanceService, private TravelOrderApprovalService $approvalService
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
            $filters['gmo_status'] = ['awaiting_approval_gmo', 'awaiting_gmo_processing', 'awaiting_gmo_booking_document_issuance', 'processed_by_gmo'];
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
        $order->load(['approvals' => fn ($query) => $query->orderBy('sequence'), 'approvals.role', 'approvals.user', 'department.divisionHead', 'booking', 'emails']);

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

    public function revision(int $id, User $user, string $remark): TravelOrder
    {
        $this->approvalService->revision($this->repository->findById($id), $user, $remark);

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

        $members = User::query()->whereIn('id', $memberIds)->where('status', 1)->get()->keyBy('id');

        abort_if($members->count() !== count($memberIds), 422, 'One or more members are invalid or inactive.');

        $sections = Section::query()->with('department')->where('status', 1)->whereIn('section_head_id', $memberIds)
            ->whereHas('department', fn ($query) => $query->where('status', 1))
            ->get()->unique('section_head_id')->keyBy('section_head_id');

        return collect($memberIds)->map(function ($memberId) use ($members, $sections) {
            $member = $members->get($memberId);
            $roles = $member->getRoleNames();

            if ($roles->contains('Div Head')) {
                return ['user_id' => $memberId, 'department_id' => null];
            }

            if ($roles->contains('Sect Head')) {
                $section = $sections->get($memberId);

                abort_if(!$section, 422, "No active section or department found for user ID {$memberId}.");

                return ['user_id' => $memberId, 'department_id' => $section->department_id];
            }

            if ($roles->contains('Dept Head')) {
                $department = Department::query()->where('status', 1)->where('dept_head_id', $memberId)->first();

                abort_if(!$department, 422, "No active department found for user ID {$memberId}.");

                return ['user_id' => $memberId, 'department_id' => $department->id];
            }

            abort(422, "User ID {$memberId} is not a Sect Head, Dept Head, or Div Head.");
        })->all();
    }

    public function create(array $data, User $user): TravelOrder
    {
        $tripType = $data['trip_type'] ?? 'individual';
        $isGroupTrip = $tripType === 'group';
        $memberIds = $data['members'] ?? [];
        $groupMembers = $isGroupTrip ? $this->getGroupTripMembers($memberIds) : [];

        $lockInfo = $this->getDepartmentLockInfo($user);
        $isDivHead = $user->getRoleNames()->contains('Div Head');

        $departmentId = $isGroupTrip
            ? (collect($groupMembers)->firstWhere('department_id', '!=', null)['department_id'] ?? null)
            : ($lockInfo['locked'] ? $lockInfo['department']['id'] : ($isDivHead ? null : ($data['department_id'] ?? null)));

        abort_if(!$departmentId && !$isDivHead && !$isGroupTrip, 422, 'Department is required.');

        if (($data['status'] ?? 'draft') === 'submitted') {
            $days = max(1, (int) Carbon::parse($data['departure_date'])->startOfDay()->diffInDays(Carbon::parse($data['return_date'])->startOfDay(), true) + 1);

            $validation = $this->advanceService->validateAdvance($data['travel_region'], $data['currency'] ?? null, (float) ($data['pocket_money'] ?? 0), (float) ($data['meal_allowance'] ?? 0), (int) $user->grade, $days);

            abort_if(!$validation['valid'], 422, $validation['message']);
        }

        return DB::transaction(function () use ($data, $user, $departmentId, $tripType, $groupMembers) {
            $data['order_number'] = $this->generateOrderNumber();
            $data['trip_type'] = $tripType;
            $data['user_id'] = $user->id;
            $data['department_id'] = $departmentId;
            $data['created_by'] = $user->id;

            $advanceData = ['meal_allowance' => $data['meal_allowance'] ?? null, 'pocket_money' => $data['pocket_money'] ?? null, 'currency' => $data['currency'] ?? null];

            unset($data['meal_allowance'], $data['pocket_money'], $data['currency'], $data['members']);

            $travelOrder = $this->repository->create($data);

            if ($tripType === 'group') {
                $travelOrder->members()->createMany($groupMembers);
            }

            $advanceData['travel_order_id'] = $travelOrder->id;
            $this->advanceRepository->create($advanceData);

            if (($data['status'] ?? 'draft') === 'submitted') {
                $this->approvalService->initFlow($travelOrder);
            }

            return $travelOrder->fresh()->load('members.user', 'members.department');
        });
    }

    public function update(int $id, array $data, User $user): TravelOrder
    {
        $travelOrder = $this->repository->findById($id);

        abort_if($travelOrder->user_id !== $user->id, 403, 'You do not have access to modify this Travel Order.');
        abort_if($travelOrder->status !== 'draft', 422, 'Only Travel Orders with draft status can be modified.');

        $tripType = $data['trip_type'] ?? $travelOrder->trip_type ?? 'individual';
        $isGroupTrip = $tripType === 'group';
        $memberIds = $data['members'] ?? [];
        $groupMembers = $isGroupTrip ? $this->getGroupTripMembers($memberIds) : [];

        $lockInfo = $this->getDepartmentLockInfo($user);
        $isDivHead = $user->getRoleNames()->contains('Div Head');

        $departmentId = $isGroupTrip
            ? (collect($groupMembers)->firstWhere('department_id', '!=', null)['department_id'] ?? null)
            : ($lockInfo['locked'] ? $lockInfo['department']['id'] : ($isDivHead ? null : ($data['department_id'] ?? $travelOrder->department_id ?? null)));

        abort_if(!$departmentId && !$isDivHead && !$isGroupTrip, 422, 'Department is required.');

        if (($data['status'] ?? 'draft') === 'submitted') {
            $days = max(1, (int) Carbon::parse($data['departure_date'])->startOfDay()->diffInDays(Carbon::parse($data['return_date'])->startOfDay(), true) + 1);

            $validation = $this->advanceService->validateAdvance($data['travel_region'], $data['currency'] ?? null, (float) ($data['pocket_money'] ?? 0), (float) ($data['meal_allowance'] ?? 0), (int) $user->grade, $days);

            abort_if(!$validation['valid'], 422, $validation['message']);
        }

        return DB::transaction(function () use ($travelOrder, $data, $departmentId, $tripType, $groupMembers) {
            $advanceData = ['meal_allowance' => $data['meal_allowance'] ?? null, 'pocket_money' => $data['pocket_money'] ?? null, 'currency' => $data['currency'] ?? null];

            unset($data['meal_allowance'], $data['pocket_money'], $data['currency'], $data['members'], $data['user_id'], $data['created_by'], $data['order_number']);

            $data['trip_type'] = $tripType;
            $data['department_id'] = $departmentId;

            $travelOrder->update($data);
            $travelOrder->members()->delete();

            if ($tripType === 'group') {
                $travelOrder->members()->createMany($groupMembers);
            }

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

            return $travelOrder->fresh()->load('members.user', 'members.department');
        });
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

    public function revisionGmo(int $id, User $user, string $remark, UploadedFile $evidence): TravelOrder
    {
        return $this->handleGmoReturnToDraft($id, $user, $remark, $evidence, 'revision');
    }

    private function handleGmoReturnToDraft(int $id, User $user, string $remark, UploadedFile $evidence, string $action): TravelOrder
    {
        $travelOrder = $this->repository->findById($id);
        $remark = trim($remark);

        abort_unless($user->hasRole('Admin-GMO'), 403, 'You are not authorized to process this Travel Order.');
        abort_if(!in_array($travelOrder->status, ['awaiting_gmo_processing', 'awaiting_gmo_booking_document_issuance'], true), 422, 'The Travel Order cannot be processed in its current status.');
        abort_if($remark === '', 422, 'Remark is required.');

        $evidencePath = $evidence->store('travel-order/gmo-evidence', 'public');

        try {
            DB::transaction(function () use ($travelOrder, $user, $remark, $evidencePath, $action) {
                $travelOrder->update(['status' => 'draft', 'gmo_remark' => $remark, 'gmo_evidence' => $evidencePath]);

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
            Storage::disk('public')->delete($evidencePath);
            throw $e;
        }

        return $this->find($id, $user);
    }
}