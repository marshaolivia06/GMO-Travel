<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annual_tickets', function (Blueprint $table) {
            $table->string('status_before_cancellation', 60)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('annual_tickets', function (Blueprint $table) {
            $table->dropColumn('status_before_cancellation');
        });
    }
};
