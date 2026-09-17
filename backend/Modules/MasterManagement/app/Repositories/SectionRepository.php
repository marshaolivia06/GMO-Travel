<?php

namespace Modules\MasterManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\MasterManagement\Models\Section;
use Modules\MasterManagement\Models\Department;
use Modules\UserManagement\Models\User;

class SectionRepository
{
    public function getAll(): Collection
    {
        return Section::with([
            'sectionHead',
            'department',
        ])
            ->where('status', 1)
            ->latest()
            ->get();
    }

    public function findById(int $id): Section
    {
        return Section::with([
            'sectionHead',
            'department',
        ])
            ->where('status', 1)
            ->findOrFail($id);
    }

    public function create(array $data): Section
    {
        return Section::create($data)
            ->load([
                'sectionHead',
                'department',
            ]);
    }

    public function update(
        Section $section,
        array $data
    ): Section {
        $section->update($data);

        return $section->fresh()->load([
            'sectionHead',
            'department',
        ]);
    }

    public function delete(Section $section): bool
    {
        return $section->update([
            'status' => 0,
        ]);
    }

    public function getOptions(): array
    {
        return [
            'section_heads' => User::where('status', 1)
                ->role('Sect Head')
                ->select(
                    'id',
                    'name'
                )
                ->get(),

            'departments' => Department::where('status', 1)
                ->select(
                    'id',
                    'name'
                )
                ->get(),
        ];
    }
}
