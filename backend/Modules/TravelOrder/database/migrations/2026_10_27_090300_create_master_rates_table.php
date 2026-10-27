<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('master_regions')->restrictOnDelete();
            $table->unsignedTinyInteger('grade_min');
            $table->unsignedTinyInteger('grade_max');
            $table->unsignedTinyInteger('hotel_star');
            $table->decimal('hotel_rate', 15, 2);
            $table->string('hotel_rate_currency', 3);
            $table->enum('meal_basis', ['limit', 'at_cost']);
            $table->decimal('meal_limit_per_meal', 15, 2)->nullable();
            $table->decimal('daily_allowance', 15, 2)->nullable();
            $table->unsignedTinyInteger('daily_allowance_max_days')->nullable();
            $table->enum('flight_class', ['economy', 'economy_business']);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
            $table->index(['region_id', 'grade_min', 'grade_max', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_rates');
    }
};