<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $userManagementPermissions = collect(['view', 'create', 'update', 'delete'])
            ->map(fn (string $action) => Permission::firstOrCreate(
                ['name' => "user-management.{$action}", 'guard_name' => 'web'],
                ['module' => 'user-management'],
            ));

        $superAdminRole->givePermissionTo($userManagementPermissions);
        $superAdmin = User::firstOrCreate(
            [
                'email' => 'admin1@gmail.com',
            ],
            [
                'name' => 'Super Admin',
                'password' => 'eco12345',
            ]
        );

        $superAdmin->assignRole($superAdminRole);
    }
}
