<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_advance_masters', function (Blueprint $table) {
            $table->id('id_travel_advance_master');
            $table->string('travel_region');
            $table->string('currency', 3);
            $table->decimal('pocket_money_limit', 15, 2);
            $table->decimal('meal_allowance_limit', 15, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_advance_masters');
    }
};
