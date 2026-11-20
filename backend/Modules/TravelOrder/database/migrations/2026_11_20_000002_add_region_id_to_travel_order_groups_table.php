<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_order_groups', function (Blueprint $table) {
            $table->foreignId('region_id')
                ->nullable()
                ->after('travel_region')
                ->constrained('master_regions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('travel_order_groups', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
        });
    }
};
