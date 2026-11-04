<?php

namespace Modules\ExternalFormRequest\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\ExternalFormRequest\Models\ExternalFormRequest;
use Modules\UserManagement\Models\User;
use Spatie\Activitylog\Models\Activity;

class ExternalFormRequestRepository
{
    private const LOG_NAME = 'external-form-request';

    public function allFor(User $user): Collection
    {
        return ExternalFormRequest::query()
            ->with(['employee.eligibleDestination', 'travelers'])
            ->when(! $user->hasRole('Admin'), fn ($query) => $query->where('employee_id', $user->id))
            ->latest()
            ->get();
    }

    public function findFor(User $user, int $id): ExternalFormRequest
    {
        return ExternalFormRequest::query()
            ->with(['travelers'])
            ->when(! $user->hasRole('Admin'), fn ($query) => $query->where('employee_id', $user->id))
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
                'causer' => $log->causer?->name,
                'created_at' => $log->created_at,
            ])
            ->values()
            ->all();
    }

    private function logActivity(User $user, ExternalFormRequest $request, string $event, string $description): void
    {
        activity(self::LOG_NAME)
            ->performedOn($request)
            ->causedBy($user)
            ->event($event)
            ->withProperties(['status' => $request->status])
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

            return $request->load('travelers');
        });
    }

    public function update(User $user, int $id, array $data, array $travelers): ExternalFormRequest
    {
        return DB::transaction(function () use ($user, $id, $data, $travelers) {
            $request = ExternalFormRequest::query()
                ->where('employee_id', $user->id)
                ->where('status', ExternalFormRequest::STATUS_DRAFT)
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
                : 'Draft submitted for approval.');

            return $request->load('travelers');
        });
    }
}
