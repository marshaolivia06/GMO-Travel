<?php

namespace Modules\TravelOrder\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\TravelOrder\Models\TravelOrderGroup;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\Section;

class TravelOrderGroupRepository
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $groups = TravelOrderGroup::query()->withCount(['members', 'travelOrders'])->with('members')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('trip_name', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()->paginate($perPage);

        return $this->attachDepartments($groups);
    }

    protected function attachDepartments(LengthAwarePaginator $groups): LengthAwarePaginator
    {
        $userIds = $groups->getCollection()->flatMap(fn ($group) => $group->members->pluck('user_id'))->unique()->values();

        $bySection = Section::with('department:id,name')->whereIn('section_head_id', $userIds)->get()
            ->mapWithKeys(fn ($section) => [$section->section_head_id => $section->department?->name]);

        $byDepartment = Department::whereIn('dept_head_id', $userIds)->pluck('name', 'dept_head_id');

        $groups->getCollection()->each(function ($group) use ($bySection, $byDepartment) {
            $group->setAttribute('departments', $group->members
                ->map(fn ($member) => $bySection[$member->user_id] ?? $byDepartment[$member->user_id] ?? null)
                ->filter()->unique()->values());
        });

        return $groups;
    }

    public function findOrFail(int $id): TravelOrderGroup
    {
        return TravelOrderGroup::with(['creator', 'members.user'])->findOrFail($id);
    }

    public function create(array $data): TravelOrderGroup
    {
        return TravelOrderGroup::create($data);
    }

    public function update(TravelOrderGroup $group, array $data): TravelOrderGroup
    {
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
