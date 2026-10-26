<?php

namespace Modules\TravelOrder\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\TravelOrder\Models\TravelOrderGroup;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\Section;
use Illuminate\Database\Eloquent\Collection;

class TravelOrderGroupRepository
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
{
    $groups = TravelOrderGroup::query()->withCount(['members', 'travelOrders'])->with('members')
        ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('trip_name', 'like', "%{$search}%"))
        ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
        ->latest()->paginate($perPage);

    $this->attachDepartments($groups->getCollection());

    return $groups;
}

public function myGroups(int $userId): Collection
{
    $groups = TravelOrderGroup::query()
        ->with('members.user:id,name')
        ->where('status', 'open')
        ->whereHas('members', fn ($query) => $query->where('user_id', $userId))
        ->orderBy('departure_date')
        ->get();

    $this->attachDepartments($groups);

    return $groups;
}

public function attachDepartments(Collection $groups): void
{
    $userIds = $groups->flatMap(fn ($group) => $group->members->pluck('user_id'))->unique()->values();

    $bySection = Section::with('department:id,name')->whereIn('section_head_id', $userIds)->get()
        ->mapWithKeys(fn ($section) => [$section->section_head_id => $section->department?->name]);

    $byDepartment = Department::whereIn('dept_head_id', $userIds)->pluck('name', 'dept_head_id');

    $groups->each(function ($group) use ($bySection, $byDepartment) {
        $group->members->each(fn ($member) => $member->setAttribute(
            'department_name',
            $bySection[$member->user_id] ?? $byDepartment[$member->user_id] ?? null
        ));

        $group->setAttribute('departments', $group->members
            ->map(fn ($member) => $member->department_name)
            ->filter()->unique()->values());
    });
}

public function findOrFail(int $id): TravelOrderGroup
{
    $group = TravelOrderGroup::with([
        'creator',
        'members.user',
        'travelOrders.approvals.role',
    ])->findOrFail($id);
    
    $this->attachDepartments(
        new Collection([$group])
    );
    
    $ordersByUser = $group->travelOrders->keyBy('user_id');
    
    $group->members->each(fn ($member) => $member->setRelation(
        'travel_order',
        $ordersByUser->get($member->user_id)
    ));
    
    return $group;
}

    public function create(array $data): TravelOrderGroup
    {
        return TravelOrderGroup::create($data);
    }

    public function update(TravelOrderGroup $group, array $data): TravelOrderGroup
{
    $group->offsetUnset('departments');
    $group->update($data);

    return $group->refresh();
}

    public function delete(TravelOrderGroup $group): bool
    {
        return $group->delete();
    }

    public function addMembers(TravelOrderGroup $group, array $userIds): void
    {
        foreach (array_unique($userIds) as $userId) $group->members()->firstOrCreate(['user_id' => $userId]);
    }

    public function removeMember(TravelOrderGroup $group, int $userId): void
    {
        $group->members()->where('user_id', $userId)->delete();
    }
}
