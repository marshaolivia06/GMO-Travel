<?php

namespace Modules\UserManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\UserManagement\Models\Permission;

class PermissionRepository
{
    public function getAll(): Collection
    {
        return Permission::where('guard_name', 'web')
            ->where('status', 1)
            ->latest()
            ->get();
    }

    public function findById(int $id): Permission
    {
        return Permission::where('guard_name', 'web')
            ->where('status', 1)
            ->findOrFail($id);
    }

    public function getByModule(string $module): Collection
    {
        return Permission::where('module', $module)
            ->where('guard_name', 'web')
            ->get();
    }

    public function create(array $data): Permission
    {
        return Permission::create($data);
    }

    public function update(
        Permission $permission,
        array $data
    ): Permission {
        $permission->update($data);

        return $permission->fresh();
    }

    public function detachRoles(Permission $permission): void
    {
        $permission->roles()->detach();
    }

    public function delete(Permission $permission): bool
    {
        return $permission->update([
            'status' => 0,
        ]);
    }
}