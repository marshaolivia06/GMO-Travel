<?php

namespace Modules\MasterManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\Section;
use Modules\UserManagement\Models\User;

class SectionRepository
{
    public function getAll(): Collection
    {
        return Section::with([
            'sectionHead',
            'supervisor',
            'department',
            'staff.user',
        ])
            ->where('status', 1)
            ->latest()
            ->get();
    }

    public function findById(int $id): Section
    {
        return Section::with([
            'sectionHead',
            'supervisor',
            'department',
            'staff.user',
        ])
            ->where('status', 1)
            ->findOrFail($id);
    }

    public function create(array $data): Section
    {
        $staffIds = $data['staff_ids'] ?? [];
        unset($data['staff_ids']);

        $section = DB::transaction(function () use ($data, $staffIds) {
            $section = Section::create($data);
            $this->syncStaff($section, $staffIds);

            return $section;
        });

        return $section->fresh()->load([
            'sectionHead',
            'supervisor',
            'department',
            'staff.user',
        ]);
    }

    public function update(
        Section $section,
        array $data
    ): Section {
        $staffIds = $data['staff_ids'] ?? [];
        unset($data['staff_ids']);

        DB::transaction(function () use ($section, $data, $staffIds) {
            $section->update($data);
            $this->syncStaff($section, $staffIds);
        });

        return $section->fresh()->load([
            'sectionHead',
            'supervisor',
            'department',
            'staff.user',
        ]);
    }

    // Sinkronisasi staff section - user yang dihapus dari daftar dikeluarkan dari pivot
    private function syncStaff(Section $section, array $staffIds): void
    {
        $current = $section->staff()->pluck('user_id')->all();

        $toAdd = array_diff($staffIds, $current);
        $toRemove = array_diff($current, $staffIds);

        foreach ($toAdd as $userId) {
            $section->staff()->firstOrCreate(['user_id' => $userId]);
        }

        if ($toRemove) {
            $section->staff()->whereIn('user_id', $toRemove)->delete();
        }
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

            'supervisors' => User::where('status', 1)
                ->role('Supervisor')
                ->select(
                    'id',
                    'name'
                )
                ->get(),

            'staffs' => User::where('status', 1)
                ->role('staff')
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
