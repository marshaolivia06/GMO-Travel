<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annual_ticket_approvals', function (Blueprint $table) {
            $table->string('flow_type')->nullable()->after('action_type');
        });
    }

    public function down(): void
    {
        Schema::table('annual_ticket_approvals', function (Blueprint $table) {
            $table->dropColumn('flow_type');
        });
    }
};
