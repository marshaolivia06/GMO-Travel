<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annual_ticket_approvals', function (Blueprint $table) {
            $table->string('action_type', 20)->nullable()->default(null)->change();
        });

        // Baris submitted bukan aksi check/approve → dikosongkan
        DB::table('annual_ticket_approvals')
            ->where('status', 'submitted')
            ->update(['action_type' => null]);
    }

    public function down(): void
    {
        Schema::table('annual_ticket_approvals', function (Blueprint $table) {
            $table->string('action_type', 20)->default('approve')->change();
        });
    }
};
