<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_form_requests', function (Blueprint $table) {
            $table->string('hotel')->nullable()->after('check_out_date');
            $table->string('room_type')->nullable()->after('hotel');
        });
    }

    public function down(): void
    {
        Schema::table('external_form_requests', function (Blueprint $table) {
            $table->dropColumn(['hotel', 'room_type']);
        });
    }
};
