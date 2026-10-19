<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_order_bookings', function (Blueprint $table) {
            $table->boolean('ticket_cancelled')->default(false);
            $table->boolean('accommodation_cancelled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('travel_order_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'ticket_cancelled',
                'accommodation_cancelled',
            ]);
        });
    }
};