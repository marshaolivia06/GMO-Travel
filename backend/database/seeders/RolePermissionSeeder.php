<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Assign Permissions to Roles
        |--------------------------------------------------------------------------
        */

        $admin->syncPermissions(
            Permission::all()
        );

        $staff->syncPermissions([
            'users.view',
            'users.create',
            'users.edit',
        ]);

        $user->syncPermissions([
            'users.view',
        ]);
    }
}