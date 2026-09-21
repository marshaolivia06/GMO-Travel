<?php

declare(strict_types=1);

namespace Modules\UserManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Modules\UserManagement\Models\User;
use Spatie\Permission\Models\Role;

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
}
