<?php

namespace Modules\ExternalFormRequest\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AuthorityMatrix\Models\AuthorityMatrix;
use Modules\ExternalFormRequest\Models\ExternalFormApproval;
use Modules\ExternalFormRequest\Models\ExternalFormRequest;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\Section;
use Modules\UserManagement\Models\User;
use Spatie\Activitylog\Models\Activity;

class ExternalFormRequestRepository
{
    private const LOG_NAME = 'external-form-request';

    public function allFor(User $user): Collection
    {
        return ExternalFormRequest::query()
            ->with(['employee.eligibleDestination', 'travelers', 'approvals'])
            ->when(! $user->hasRole('Admin'), fn ($query) => $query->where(function ($query) use ($user) {
                $query->where('employee_id', $user->id)
                    ->orWhereHas('approvals', fn ($approvals) => $approvals
                        ->where('user_id', $user->id)
                        ->where('status', 'pending')
                        // Hanya giliran berjalan - step pending berikutnya tidak dihitung
                        ->whereNotExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('external_form_approvals as prev')
                                ->whereColumn('prev.external_form_request_id', 'external_form_approvals.external_form_request_id')
                                ->where('prev.status', 'pending')
                                ->whereColumn('prev.step', '<', 'external_form_approvals.step');
                        }));
            }))
            ->latest()
            ->get()
            ->each(function (ExternalFormRequest $form) use ($user) {
                $pending = $form->approvals->first(
                    fn (ExternalFormApproval $approval) => $approval->status === 'pending' && $approval->user_id === $user->id,
                );

                $form->setAttribute('can_approve', (bool) $pending);
                $form->setAttribute('pending_action', $pending?->action_type === 'check' ? 'Review' : 'Approve');
            });
    }

    public function findFor(User $user, int $id): ExternalFormRequest
    {
        return ExternalFormRequest::query()
            ->with(['travelers', 'approvals'])
            ->when(! $user->hasRole('Admin'), fn ($query) => $query->where(function ($query) use ($user) {
                $query->where('employee_id', $user->id)
                    ->orWhereHas('approvals', fn ($approvals) => $approvals->where('user_id', $user->id));
            }))
            ->findOrFail($id);
    }

    public function historyFor(ExternalFormRequest $request): array
    {
        return Activity::with('causer')
            ->where('log_name', self::LOG_NAME)
            ->where('subject_type', $request->getMorphClass())
            ->where('subject_id', $request->id)
            ->orderBy('id')
            ->get()
            ->map(fn (Activity $log) => [
                'id' => $log->id,
                'event' => $log->event,
                'description' => $log->description,
                'properties' => $log->properties->get('status') ? ['status' => $log->properties->get('status')] : [],
                'role' => $log->properties->get('role') ?? '-',
                'round' => $log->properties->get('round', 1),
                'note' => $log->properties->get('note'),
                'causer' => $log->causer?->name,
                'created_at' => $log->created_at,
            ])
            ->values()
            ->all();
    }

    public function approve(User $user, int $id): ExternalFormRequest
    {
        return $this->decide($user, $id, 'approve');
    }

    public function revision(User $user, int $id, ?string $remark = null): ExternalFormRequest
    {
        return $this->decide($user, $id, 'revision', $remark);
    }

    public function reject(User $user, int $id, ?string $remark = null): ExternalFormRequest
    {
        return $this->decide($user, $id, 'reject', $remark);
    }

    public function decide(User $user, int $id, string $action, ?string $remark = null): ExternalFormRequest
    {
        return DB::transaction(function () use ($user, $id, $action, $remark) {
            $form = ExternalFormRequest::query()
                ->with('approvals')
                ->whereHas('approvals', fn ($approvals) => $approvals
                    ->where('user_id', $user->id)
                    ->where('status', 'pending'))
                ->findOrFail($id);

            $firstPending = $form->approvals->sortBy('step')->first(
                fn (ExternalFormApproval $approval) => $approval->status === 'pending',
            );

            $approval = $form->approvals->first(
                fn (ExternalFormApproval $row) => $row->status === 'pending' && $row->user_id === $user->id,
            );

            // Wajib diproses sesuai urutan step authority matrix
            if (! $approval || $approval->id !== $firstPending?->id) {
                abort(422, 'This request is not awaiting your approval yet.');
            }

            $role = $approval->role?->name ?? $approval->label;

            if ($action === 'revision') {
                $approval->update(['status' => 'revision', 'acted_at' => now()]);

                // Sisa step pending berikutnya dibatalkan - round baru dibuat saat resubmit
                $form->approvals()->where('status', 'pending')->update(['status' => 'cancelled']);

                $form->update(['status' => 'revision_required']);

                $this->logActivity($user, $form->refresh(), 'revision',
                    'External form request returned for revision by ' . str_replace(' ', '.', (string) $role) . '.', ['note' => $remark]);

                return $form->refresh()->load(['travelers', 'approvals']);
            }

            if ($action === 'reject') {
                $approval->update(['status' => 'rejected', 'acted_at' => now()]);

                $form->approvals()->where('status', 'pending')->update(['status' => 'cancelled']);

                $form->update(['status' => 'rejected']);

                $this->logActivity($user, $form->refresh(), 'rejected',
                    'External form request rejected by ' . str_replace(' ', '.', (string) $role) . '.', ['note' => $remark]);

                return $form->refresh()->load(['travelers', 'approvals']);
            }

            $isReview = $approval->action_type === 'check';

            $approval->update([
                'status' => $isReview ? 'reviewed' : 'approved',
                'acted_at' => now(),
            ]);

            $next = $form->approvals->sortBy('step')->first(
                fn (ExternalFormApproval $row) => $row->status === 'pending',
            );

            $normalize = fn (string $value) => strtolower(preg_replace('/[^a-z0-9]+/i', '', $value));

            $statusByRole = [
                'depthead' => ExternalFormRequest::STATUS_AWAITING_DEPT_HEAD,
                'admingmo' => ExternalFormRequest::STATUS_AWAITING_REVIEW_GMO,
                'divhead' => ExternalFormRequest::STATUS_AWAITING_DIV_HEAD,
            ];

            $form->update([
                'status' => $next
                    ? ($statusByRole[$normalize($next->role?->name ?? $next->label ?? '')] ?? $form->status)
                    : ExternalFormRequest::STATUS_BOOKING_PAYMENT,
            ]);

            $this->logActivity($user, $form->refresh(), $isReview ? 'checked' : 'approved',
                $isReview
                    ? 'External form request reviewed by Admin-GMO.'
                    : 'External form request approved by ' . str_replace(' ', '.', (string) $role) . '.');

            return $form->refresh()->load(['travelers', 'approvals']);
        });
    }

    // Round = jumlah kali submit; event submit membuka round baru
    public function currentRound(ExternalFormRequest $request): int
    {
        return Activity::where('log_name', self::LOG_NAME)
            ->where('subject_type', $request->getMorphClass())
            ->where('subject_id', $request->id)
            ->where('event', 'submit')
            ->count();
    }

    private function logActivity(User $user, ExternalFormRequest $request, string $event, string $description, array $props = []): void
    {
        $round = max(1, $this->currentRound($request) + ($event === 'submit' ? 1 : 0));

        activity(self::LOG_NAME)
            ->performedOn($request)
            ->causedBy($user)
            ->event($event)
            ->withProperties(array_merge([
                'status' => $request->status,
                'role' => $user->roles->first()?->name,
                'round' => $round,
            ], $props))
            ->log($description);
    }

    public function create(User $user, array $data, array $travelers): ExternalFormRequest
    {
        return DB::transaction(function () use ($user, $data, $travelers) {
            $request = ExternalFormRequest::create([
                'employee_id' => $user->id,
                'type' => $data['type'],
                'status' => $data['action'] === 'draft'
                    ? ExternalFormRequest::STATUS_DRAFT
                    : ExternalFormRequest::STATUS_AWAITING_DEPT_HEAD,
                'document_number' => $data['action'] === 'draft' ? null : ExternalFormRequest::generateDocumentNumber(),
                'transport_mode' => $data['transport_mode'] ?? null,
                'vendor_customer' => $data['vendor_customer'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'trip_type' => $data['trip_type'] ?? null,
                'travel_from' => $data['travel_from'] ?? null,
                'departure_date' => $data['departure_date'] ?? null,
                'travel_to' => $data['travel_to'] ?? null,
                'return_date' => $data['return_date'] ?? null,
                'check_in_date' => $data['check_in_date'] ?? null,
                'check_out_date' => $data['check_out_date'] ?? null,
                'remark' => $data['remark'] ?? null,
            ]);

            foreach ($travelers as $traveler) {
                $request->travelers()->create(['name' => $traveler['name']]);
            }

            $this->logActivity($user, $request, $data['action'], $data['action'] === 'draft'
                ? 'External form request saved as draft.'
                : 'External form request submitted.');

            if ($data['action'] === 'submit') {
                $this->createApprovalChain($user, $request);
            }

            return $request->load('travelers');
        });
    }

    public function update(User $user, int $id, array $data, array $travelers): ExternalFormRequest
    {
        return DB::transaction(function () use ($user, $id, $data, $travelers) {
            $request = ExternalFormRequest::query()
                ->where('employee_id', $user->id)
                ->whereIn('status', [ExternalFormRequest::STATUS_DRAFT, 'revision_required'])
                ->findOrFail($id);

            $request->update([
                'type' => $data['type'],
                'status' => $data['action'] === 'draft'
                    ? ExternalFormRequest::STATUS_DRAFT
                    : ExternalFormRequest::STATUS_AWAITING_DEPT_HEAD,
                'document_number' => $data['action'] === 'draft' ? null : ExternalFormRequest::generateDocumentNumber(),
                'transport_mode' => $data['transport_mode'] ?? null,
                'vendor_customer' => $data['vendor_customer'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'trip_type' => $data['trip_type'] ?? null,
                'travel_from' => $data['travel_from'] ?? null,
                'departure_date' => $data['departure_date'] ?? null,
                'travel_to' => $data['travel_to'] ?? null,
                'return_date' => $data['return_date'] ?? null,
                'check_in_date' => $data['check_in_date'] ?? null,
                'check_out_date' => $data['check_out_date'] ?? null,
                'remark' => $data['remark'] ?? null,
            ]);

            $request->travelers()->delete();

            foreach ($travelers as $traveler) {
                $request->travelers()->create(['name' => $traveler['name']]);
            }

            $this->logActivity($user, $request->refresh(), $data['action'], $data['action'] === 'draft'
                ? 'Draft updated.'
                : 'External form request resubmitted.');

            if ($data['action'] === 'submit') {
                $this->createApprovalChain($user, $request);
            }

            return $request->load('travelers');
        });
    }

    // Rantai approval dibangun saat submit per round - baris lama dipertahankan sebagai history,
    // pending yang menggantung dibatalkan, requester + tahapan authority matrix "External Form Request"
    private function createApprovalChain(User $user, ExternalFormRequest $form): void
    {
        $form->approvals()->where('status', 'pending')->update(['status' => 'cancelled']);

        $round = max(1, $this->currentRound($form));

        $userRole = $user->roles->first();

        $form->approvals()->create([
            'round' => $round,
            'step' => 0,
            'role_id' => $userRole?->id,
            'label' => $userRole?->name,
            'action_type' => 'submit',
            'user_id' => $user->id,
            'status' => 'submitted',
            'acted_at' => now(),
        ]);

        $matrix = AuthorityMatrix::with('steps.role')
            ->where('document_type', 'External Form Request')
            ->where('status', true)
            ->first();

        if (! $matrix) {
            return;
        }

        $approvers = $this->resolveApprovers($form);

        $normalize = fn (string $value) => strtolower(preg_replace('/[^a-z0-9]+/i', '', $value));

        foreach ($matrix->steps->sortBy('step') as $step) {
            $key = $normalize($step->role?->name ?? $step->label ?? '');

            $form->approvals()->create([
                'round' => $round,
                'step' => $step->step,
                'role_id' => $step->role_id,
                'label' => $step->label ?: str_replace(' ', '.', $step->role?->name ?? '-'),
                'action_type' => $step->action_type,
                'user_id' => $approvers[$key] ?? null,
                'status' => 'pending',
            ]);
        }
    }

    // Approver mengikuti requester: Sect.Head -> section -> department -> Dept.Head / Div.Head, Admin-GMO dari role
    private function resolveApprovers(ExternalFormRequest $form): array
    {
        $section = Section::with('department')->where('section_head_id', $form->employee_id)->first();

        $department = $section?->department
            ?? Department::find($form->employee?->department_id)
            ?? Department::where('dept_head_id', $form->employee_id)->first();

        $divisionHead = $department?->divisionHead ? User::find($department->divisionHead->division_head_id) : null;

        $adminGmo = User::whereHas('roles', fn ($query) => $query->where('name', 'Admin-GMO'))->first();

        return [
            'depthead' => $department?->deptHead?->id,
            'admingmo' => $adminGmo?->id,
            'divhead' => $divisionHead?->id,
        ];
    }
}
