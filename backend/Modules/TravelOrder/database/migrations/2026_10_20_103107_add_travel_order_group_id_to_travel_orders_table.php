<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->foreignId('travel_order_group_id')
                ->nullable()
                ->after('trip_type')
                ->constrained('travel_order_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropForeign(['travel_order_group_id']);
            $table->dropColumn('travel_order_group_id');
        });
    }
};