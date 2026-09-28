<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('travel_advance_masters', function (Blueprint $table) {
            $table->unsignedTinyInteger('grade_min')->nullable()->after('travel_region');
            $table->unsignedTinyInteger('grade_max')->nullable()->after('grade_min');
            $table->string('country')->nullable()->after('grade_max');
        });
    }
    
    public function down(): void
    {
        Schema::table('travel_advance_masters', function (Blueprint $table) {
            $table->dropColumn(['grade_min', 'grade_max', 'country']);
        });
    }
};
