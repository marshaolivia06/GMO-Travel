<?php

use Illuminate\Database\Migrations\Migration;
use Modules\MasterManagement\Models\MasterEmployee;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Hak Annual Ticket per karyawan dijadikan permission (dikelola otomatis dari
 * master_employees.annual_ticket_eligible) agar FE cukup cek auth.can(...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(
            ['name' => MasterEmployee::ANNUAL_TICKET_PERMISSION, 'guard_name' => 'web'],
            ['module' => MasterEmployee::ANNUAL_TICKET_PERMISSION_MODULE],
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Sinkronisasi sekali jalan untuk karyawan yang sudah ada
        MasterEmployee::query()->each(fn (MasterEmployee $employee) => $employee->syncAnnualTicketPermission());
    }

    public function down(): void
    {
        Permission::where('name', MasterEmployee::ANNUAL_TICKET_PERMISSION)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
