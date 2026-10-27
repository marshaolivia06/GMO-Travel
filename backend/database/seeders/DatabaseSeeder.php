<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed roles & permissions
        $this->call([
            RolePermissionSeeder::class,
        ]);

        // Create admin user
        $admin = User::firstOrCreate(
            [
                'email' => 'admin@gmail.com',
            ],
            [
                'name' => 'Admin',
                'password' => 'eco12345',
            ]
        );

        // Assign admin role
        $admin->assignRole('admin');
    }
}
