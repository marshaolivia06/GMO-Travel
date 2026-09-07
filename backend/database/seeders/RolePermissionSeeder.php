<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $staff = Role::firstOrCreate([
            'name' => 'staff',
            'guard_name' => 'web',
        ]);

        $user = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        $modules = [
            'user-management',
            'travel-order',
            'annual-leave',
        ];

        $actions = [
            'view',
            'create',
            'update',
            'delete',
        ];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$module}.{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }

        $admin->syncPermissions(
            Permission::where('guard_name', 'web')->get()
        );

        $staff->syncPermissions([
            'user-management.view',
            'user-management.create',
            'user-management.update',
            'travel-order.view',
            'travel-order.create',
            'travel-order.update',
            'annual-leave.view',
            'annual-leave.create',
            'annual-leave.update',
        ]);

        $user->syncPermissions([
            'user-management.view',
            'travel-order.view',
            'travel-order.create',
            'annual-leave.view',
            'annual-leave.create',
        ]);
    }
}
