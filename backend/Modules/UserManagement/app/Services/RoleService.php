<?php

namespace Modules\UserManagement\Services;

use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Repositories\RoleRepository;
use Spatie\Permission\Models\Permission;

class RoleService
{
    public function __construct(
        private RoleRepository $roleRepository
    ) {
    }

    public function updateRole(int $id, array $data): Role
    {
        $role = $this->roleRepository->findById($id);

        $role = $this->roleRepository->update(
            $role,
            [
                'name' => $data['name'],
            ]
        );

        if (array_key_exists('permissions', $data)) {
            $permissions = Permission::whereIn(
                'id',
                $data['permissions']
            )->get();

            $role->syncPermissions($permissions);
        }

        return $role->fresh()->load('permissions');
    }
}
