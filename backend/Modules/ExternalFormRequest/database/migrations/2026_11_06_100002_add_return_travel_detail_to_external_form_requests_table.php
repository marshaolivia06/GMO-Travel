<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_form_requests', function (Blueprint $table) {
            $table->string('return_operator')->nullable()->after('operator');
            $table->string('return_flight_vessel_no')->nullable()->after('return_operator');
            $table->dateTime('return_atd')->nullable()->after('departure_ata');
            $table->dateTime('return_ata')->nullable()->after('return_atd');
        });
    }

    public function down(): void
    {
        Schema::table('external_form_requests', function (Blueprint $table) {
            $table->dropColumn(['return_operator', 'return_flight_vessel_no', 'return_atd', 'return_ata']);
        });
    }
};
