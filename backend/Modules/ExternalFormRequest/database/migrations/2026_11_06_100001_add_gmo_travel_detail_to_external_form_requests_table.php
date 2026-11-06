<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_form_requests', function (Blueprint $table) {
            $table->string('operator')->nullable()->after('travel_to');
            $table->string('flight_vessel_no')->nullable()->after('operator');
            $table->dateTime('departure_atd')->nullable()->after('departure_date');
            $table->dateTime('departure_ata')->nullable()->after('departure_atd');
        });
    }

    public function down(): void
    {
        Schema::table('external_form_requests', function (Blueprint $table) {
            $table->dropColumn(['operator', 'flight_vessel_no', 'departure_atd', 'departure_ata']);
        });
    }
};
