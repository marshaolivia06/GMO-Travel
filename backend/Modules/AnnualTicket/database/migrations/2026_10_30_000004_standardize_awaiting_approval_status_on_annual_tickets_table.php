<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Status lama "awaiting_approve_<role>" distandarkan menjadi "awaiting_approval_<role>"
        DB::statement("UPDATE annual_tickets SET status = CONCAT('awaiting_approval_', SUBSTRING(status, LENGTH('awaiting_approve_') + 1)) WHERE status LIKE 'awaiting_approve_%'");
    }

    public function down(): void
    {
        DB::statement("UPDATE annual_tickets SET status = CONCAT('awaiting_approve_', SUBSTRING(status, LENGTH('awaiting_approval_') + 1)) WHERE status LIKE 'awaiting_approval_%'");
    }
};
