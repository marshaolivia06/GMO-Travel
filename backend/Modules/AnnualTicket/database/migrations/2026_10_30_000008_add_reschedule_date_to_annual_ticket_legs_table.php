<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annual_ticket_legs', function (Blueprint $table) {
            // Tanggal asli tetap tersimpan di travel_date; reschedule_date = tanggal hasil reschedule GMO
            $table->date('reschedule_date')->nullable()->after('travel_date');
        });
    }

    public function down(): void
    {
        Schema::table('annual_ticket_legs', function (Blueprint $table) {
            $table->dropColumn('reschedule_date');
        });
    }
};
