<?php

namespace Modules\MasterManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\MasterManagement\Models\Category;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\MasterDivision;
use Modules\UserManagement\Models\User;

class DepartmentRepository
{
    public function getAll(): Collection
    {
        return Department::with([
            'category',
            'deptHead',
            'deptAdmin',
            'divisionHead',
        ])
            ->where('status', 1)
            ->latest()
            ->get();
    }

    public function findById(int $id): Department
    {
        return Department::with([
            'category',
            'deptHead',
            'deptAdmin',
            'divisionHead',
        ])
            ->where('status', 1)
            ->findOrFail($id);
    }

    public function create(array $data): Department
    {
        return Department::create($data)
            ->load([
                'category',
                'deptHead',
                'deptAdmin',
                'divisionHead',
            ]);
    }

    public function update(
        Department $department,
        array $data
    ): Department {
        $department->update($data);

        return $department->fresh()->load([
            'category',
            'deptHead',
            'deptAdmin',
            'divisionHead',
        ]);
    }

    public function delete(Department $department): bool
    {
        return $department->update([
            'status' => 0,
        ]);
    }

    public function getOptions(): array
    {
        return [
            'categories' => Category::where('status', 1)
                ->select(
                    'id',
                    'name'
                )
                ->get(),

            'dept_heads' => User::where('status', 1)
                ->role('Dept Head')
                ->select(
                    'id',
                    'name'
                )
                ->get(),

            'dept_admins' => User::where('status', 1)
                ->role('Dept Admin')
                ->select(
                    'id',
                    'name'
                )
                ->get(),

            'division_heads' => MasterDivision::select(
                'id',
                'division_name'
            )->get(),
        ];
    }
}
