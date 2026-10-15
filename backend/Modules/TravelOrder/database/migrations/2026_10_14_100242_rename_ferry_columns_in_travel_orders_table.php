<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->renameColumn('ferry_ticket_type', 'ticket_type');
            $table->renameColumn('ferry_arrangement', 'ticket_arrangement');
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->renameColumn('ticket_type', 'ferry_ticket_type');
            $table->renameColumn('ticket_arrangement', 'ferry_arrangement');
        });
    }
};
