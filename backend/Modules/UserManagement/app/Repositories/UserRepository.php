<?php

namespace Modules\UserManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\UserManagement\Models\User;

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

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(
        User $user,
        array $data
    ): User {
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
