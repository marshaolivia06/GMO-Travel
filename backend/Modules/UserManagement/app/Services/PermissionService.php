<?php

namespace Modules\UserManagement\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Repositories\PermissionRepository;

class PermissionService
{
    public function __construct(
        private PermissionRepository $permissionRepository
    ) {
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

            $permissions = $this->permissionRepository
                ->getByModule($oldModule);

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

                    $this->permissionRepository->update(
                        $permission,
                        [
                            'name' => "{$moduleName}.{$action}",
                            'module' => $newModule,
                            'guard_name' => 'web',
                            'status' => 1,
                        ]
                    );
                } else {
                    $this->permissionRepository->create([
                        'name' => "{$moduleName}.{$action}",
                        'module' => $newModule,
                        'guard_name' => 'web',
                        'status' => 1,
                    ]);
                }
            }

            foreach ($existingByAction as $action => $permission) {
                if (!in_array($action, $actions, true)) {
                    $this->permissionRepository->update(
                        $permission,
                        [
                            'status' => 0,
                        ]
                    );
                }
            }

            return [
                'message' => 'Permission updated successfully.',
                'data' => $this->permissionRepository
                    ->getByModule($newModule),
            ];
        });
    }
}