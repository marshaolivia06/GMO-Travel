<?php

declare(strict_types=1);

namespace Modules\UserManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Modules\UserManagement\Models\User;
use Spatie\Permission\Models\Role;
use Modules\MasterManagement\Models\Section;

class UserRepository
{
    public function getAll(): Collection
    {
        return User::with('roles.permissions')
            ->where('status', 1)
            ->latest()
            ->get();
    }

    public function findById(int $id): User
    {
        return User::with('roles.permissions')
            ->where('status', 1)
            ->findOrFail($id);
    }

    public function createUser(array $data): User
    {
        $role = Role::where('name', $data['role'])
            ->where('guard_name', 'web')
            ->firstOrFail();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'grade' => $data['grade'] ?? null,
        ]);

        $user->assignRole($role);

        return $user->fresh()->load('roles.permissions');
    }

    public function updateUser(int $id, array $data): User
    {
        $user = $this->findById($id);

        if (isset($data['password']) && $data['password'] !== '') {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (isset($data['role'])) {
            $role = Role::where('name', $data['role'])
                ->where('guard_name', 'web')
                ->firstOrFail();

            unset($data['role']);

            $user->update($data);
            $user->syncRoles([$role]);

            return $user->fresh()->load('roles.permissions');
        }

        $user->update($data);

        return $user->fresh()->load('roles.permissions');
    }

    public function delete(User $user): bool
    {
        return $user->update([
            'status' => 0,
        ]);
    }

    
public function getGroupTripUsers(): Collection
{
    $users = User::with('roles.permissions')
        ->where('status', 1)
        ->whereHas('roles', function ($query) {
            $query->whereIn('name', [
                'Sect Head',
                'Dept Head',
                'Div Head',
            ]);
        })
        ->latest()
        ->get();

    $userIds = $users->pluck('id');

    $sections = Section::with('department:id,name')
        ->where('status', 1)
        ->whereIn('section_head_id', $userIds)
        ->get()
        ->keyBy('section_head_id');

    return $users->map(function (User $user) use ($sections) {
        $roles = $user->getRoleNames();

        if ($roles->contains('Div Head')) {
    $user->setAttribute('section_name', null);
    $user->setAttribute('department_name', null);
    $user->setAttribute('department_id', null);

    return $user;
}

        if ($roles->contains('Sect Head')) {
            $section = $sections->get($user->id);

            $user->setAttribute('section_name', $section?->name);
            $user->setAttribute(
                'department_name',
                $section?->department?->name
            );
            $user->setAttribute(
                'department_id',
                $section?->department_id
            );

            return $user;
        }

        $department = \Modules\MasterManagement\Models\Department::query()
            ->where('status', 1)
            ->where('dept_head_id', $user->id)
            ->first();

        $user->setAttribute('section_name', null);
        $user->setAttribute('department_name', $department?->name);
        $user->setAttribute('department_id', $department?->id);

        return $user;
    });
}

}
