<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_order_booking_cancellations', function (Blueprint $table) { $table->json('confirmation_files')->nullable()->after('confirmation_file'); });
    }

    public function down(): void
    {
        Schema::table('travel_order_booking_cancellations', function (Blueprint $table) { $table->dropColumn('confirmation_files'); });
    }
};
