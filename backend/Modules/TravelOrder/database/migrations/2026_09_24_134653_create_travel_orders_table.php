<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('trip_type')->default('individual');
            $table->string('status')->default('submitted');

            // Employee information
            // User information
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('department_id')->constrained('departments');

            // Trip information
            $table->string('travel_from');
            $table->string('travel_to');
            $table->date('departure_date');
            $table->time('departure_time');
            $table->date('return_date');
            $table->time('return_time');
            $table->text('purpose');
            $table->text('remarks')->nullable();

            // Region & travel advance
            $table->string('travel_region');
            $table->string('meal_currency', 3)->nullable();
            $table->decimal('meal_allowance', 15, 2)->nullable();
            $table->string('pocket_currency', 3)->nullable();
            $table->decimal('pocket_money', 15, 2)->nullable();

            // Ticket & accommodation
            $table->string('ferry_ticket_type');
            $table->string('ferry_arrangement');
            $table->string('accommodation_arrangement');

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_orders');
    }
};