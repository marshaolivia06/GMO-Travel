<?php

declare(strict_types=1);

namespace Modules\UserManagement\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class RoleRepository
{
    public function __construct(
        private PermissionRegistrar $permissionRegistrar
    ) {
    }

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
        return DB::transaction(function () use ($data) {
            $permissionIds = $data['permissions'] ?? [];

            unset($data['permissions']);

            $now = now();

            $data['created_at'] = $now;
            $data['updated_at'] = $now;

            $id = DB::table('roles')->insertGetId($data);

            if (!empty($permissionIds)) {
                $permissions = DB::table('permissions')
                    ->whereIn('id', $permissionIds)
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

            $this->permissionRegistrar
                ->forgetCachedPermissions();

            return $this->findById($id);
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            DB::table('roles')
                ->where('id', $id)
                ->where('status', 1)
                ->update([
                    'name' => $data['name'],
                    'updated_at' => now(),
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

                $this->permissionRegistrar
                    ->forgetCachedPermissions();
            }

            return $this->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $deleted = DB::table('roles')
                ->where('id', $id)
                ->where('status', 1)
                ->update([
                    'status' => 0,
                    'updated_at' => now(),
                ]);

            if ($deleted > 0) {
                $this->permissionRegistrar
                    ->forgetCachedPermissions();
            }

            return $deleted > 0;
        });
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
