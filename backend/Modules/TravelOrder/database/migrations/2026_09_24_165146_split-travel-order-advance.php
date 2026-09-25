<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_order_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_order_id')
                ->constrained('travel_orders')
                ->cascadeOnDelete();

            $table->decimal('meal_allowance', 15, 2)->nullable();
            $table->decimal('pocket_money', 15, 2)->nullable();
            $table->string('currency')->nullable();

            $table->timestamps();
        });

        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropColumn([
                'meal_allowance',
                'meal_currency',
                'pocket_money',
                'pocket_currency',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->decimal('meal_allowance', 15, 2)->nullable();
            $table->string('meal_currency')->nullable();
            $table->decimal('pocket_money', 15, 2)->nullable();
            $table->string('pocket_currency')->nullable();
        });

        Schema::dropIfExists('travel_order_advances');
    }
};
