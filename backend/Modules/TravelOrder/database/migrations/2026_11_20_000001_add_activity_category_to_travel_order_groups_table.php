<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_order_groups', function (Blueprint $table) {
            $table->string('activity_category')
                ->nullable()
                ->after('travel_region');

            $table->index('activity_category');
        });
    }

    public function down(): void
    {
        Schema::table('travel_order_groups', function (Blueprint $table) {
            $table->dropIndex(['activity_category']);
            $table->dropColumn('activity_category');
        });
    }
};
