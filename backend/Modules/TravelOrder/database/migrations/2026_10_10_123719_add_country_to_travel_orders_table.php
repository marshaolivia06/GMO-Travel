<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->string('country')->nullable()->after('travel_region');
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
