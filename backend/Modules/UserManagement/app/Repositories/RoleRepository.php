<?php

namespace Modules\UserManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\UserManagement\Models\Role;

class RoleRepository
{
    public function getAll(): Collection
    {
        return Role::with('permissions')
            ->where('status', 1)
            ->latest()
            ->get();
    }

    public function findById(int $id): Role
    {
        return Role::with('permissions')
            ->where('status', 1)
            ->findOrFail($id);
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $role->fresh()->load('permissions');
    }

    public function delete(Role $role): bool
    {
        return $role->update([
            'status' => 0,
        ]);
    }
}
