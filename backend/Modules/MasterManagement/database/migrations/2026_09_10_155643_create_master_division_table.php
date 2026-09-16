<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_division', function (Blueprint $table) {
            $table->id();
            $table->string('division_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_division');
    }
};
