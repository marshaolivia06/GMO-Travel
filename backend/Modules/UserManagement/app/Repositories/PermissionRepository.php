<?php

declare(strict_types=1);

namespace Modules\UserManagement\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PermissionRepository
{
    public function getAll(): Collection
    {
        return DB::table('permissions')
            ->where('guard_name', 'web')
            ->where('status', 1)
            ->orderByDesc('created_at')
            ->get();
    }

    public function findById(int $id)
    {
        $permission = DB::table('permissions')
            ->where('guard_name', 'web')
            ->where('status', 1)
            ->where('id', $id)
            ->first();

        if (!$permission) {
            abort(404);
        }

        return $permission;
    }

    public function getByModule(string $module): Collection
    {
        return DB::table('permissions')
            ->where('module', $module)
            ->where('guard_name', 'web')
            ->where('status', 1)
            ->get();
    }

    public function create(array $data)
    {
        $now = now();

        $id = DB::table('permissions')->insertGetId(
            array_merge($data, [
                'created_at' => $now,
                'updated_at' => $now,
            ])
        );

        return DB::table('permissions')
            ->where('id', $id)
            ->first();
    }

    public function update(int $id, array $data)
    {
        DB::table('permissions')
            ->where('id', $id)
            ->update(array_merge($data, [
                'updated_at' => now(),
            ]));

        return $this->findById($id);
    }

    public function detachRoles(int $permissionId): void
    {
        DB::table('role_has_permissions')
            ->where('permission_id', $permissionId)
            ->delete();
    }

    public function delete(int $id): bool
    {
        return DB::table('permissions')
            ->where('id', $id)
            ->where('guard_name', 'web')
            ->where('status', 1)
            ->update([
                'status' => 0,
            ]) > 0;
    }

    public function syncPermissions(array $data): array
    {
        $oldModule = trim($data['old_module']);
        $newModule = trim($data['module']);

        $actions = array_values(
            array_unique($data['actions'])
        );

        return DB::transaction(function () use (
            $oldModule,
            $newModule,
            $actions
        ) {
            $moduleName = strtolower(
                preg_replace('/\s+/', '-', $newModule)
            );

            $permissions = $this->getByModule($oldModule);

            if ($permissions->isEmpty()) {
                return [
                    'message' => 'Permission tidak ditemukan.',
                ];
            }

            $existingByAction = [];

            foreach ($permissions as $permission) {
                $action = strtolower(
                    last(explode('.', $permission->name))
                );

                $existingByAction[$action] = $permission;
            }

            foreach ($actions as $action) {
                if (isset($existingByAction[$action])) {
                    $permission = $existingByAction[$action];

                    $this->update(
                        $permission->id,
                        [
                            'name' => "{$moduleName}.{$action}",
                            'module' => $newModule,
                            'guard_name' => 'web',
                            'status' => 1,
                        ]
                    );
                } else {
                    $this->create([
                        'name' => "{$moduleName}.{$action}",
                        'module' => $newModule,
                        'guard_name' => 'web',
                        'status' => 1,
                    ]);
                }
            }

            foreach ($existingByAction as $action => $permission) {
                if (!in_array($action, $actions, true)) {
                    DB::table('permissions')
                        ->where('id', $permission->id)
                        ->update([
                            'status' => 0,
                        ]);
                }
            }

            return [
                'message' => 'Permission updated successfully.',
                'data' => $this->getByModule($newModule),
            ];
        });
    }
}
