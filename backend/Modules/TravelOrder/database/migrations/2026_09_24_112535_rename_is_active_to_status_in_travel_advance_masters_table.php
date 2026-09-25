<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_advance_masters', function (Blueprint $table) {
            $table->renameColumn('is_active', 'status');
        });
    }

    public function down(): void
    {
        Schema::table('travel_advance_masters', function (Blueprint $table) {
            $table->renameColumn('status', 'is_active');
        });
    }
};
