<?php

namespace Modules\UserManagement\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoleRepository
{
    public function getAll(): Collection
    {
        $roles = DB::table('roles')
            ->where('status', 1)
            ->orderByDesc('created_at')
            ->get();

        return $this->loadPermissions($roles);
    }

    public function findById(int $id)
    {
        $role = DB::table('roles')
            ->where('status', 1)
            ->where('id', $id)
            ->first();

        if (!$role) {
            abort(404);
        }

        return $this->loadPermissions(
            collect([$role])
        )->first();
    }

    public function create(array $data)
    {
        $id = DB::table('roles')->insertGetId($data);

        return $this->findById($id);
    }

    public function update(int $id, array $data)
    {
        DB::transaction(function () use ($id, $data) {
            DB::table('roles')
                ->where('id', $id)
                ->where('status', 1)
                ->update([
                    'name' => $data['name'],
                ]);

            if (array_key_exists('permissions', $data)) {
                DB::table('role_has_permissions')
                    ->where('role_id', $id)
                    ->delete();

                if (!empty($data['permissions'])) {
                    $permissions = DB::table('permissions')
                        ->whereIn('id', $data['permissions'])
                        ->pluck('id');

                    $insertData = $permissions
                        ->map(fn ($permissionId) => [
                            'role_id' => $id,
                            'permission_id' => $permissionId,
                        ])
                        ->toArray();

                    if (!empty($insertData)) {
                        DB::table('role_has_permissions')
                            ->insert($insertData);
                    }
                }
            }
        });

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return DB::table('roles')
            ->where('id', $id)
            ->where('status', 1)
            ->update([
                'status' => 0,
            ]) > 0;
    }

    private function loadPermissions(Collection $roles): Collection
    {
        if ($roles->isEmpty()) {
            return $roles;
        }

        $permissions = DB::table('role_has_permissions')
            ->leftJoin(
                'permissions',
                'role_has_permissions.permission_id',
                '=',
                'permissions.id'
            )
            ->whereIn(
                'role_has_permissions.role_id',
                $roles->pluck('id')
            )
            ->select(
                'role_has_permissions.role_id',
                'permissions.id',
                'permissions.name',
                'permissions.guard_name'
            )
            ->get()
            ->groupBy('role_id');

        return $roles->map(function ($role) use ($permissions) {
            $role->permissions = $permissions
                ->get($role->id, collect())
                ->values();

            return $role;
        });
    }
}
