<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('destination_code', 20)->unique();
            $table->string('city_name', 100);
            $table->string('country', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_destinations');
    }
};