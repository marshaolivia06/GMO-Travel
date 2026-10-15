<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_order_bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('travel_order_id')
                ->constrained('travel_orders')
                ->cascadeOnDelete();

            // Adjust Time
            $table->date('departure_date')->nullable();
            $table->time('departure_time')->nullable();
            $table->date('return_date')->nullable();
            $table->time('return_time')->nullable();

            // Ticket Booking
            $table->string('ticket_currency', 10)->nullable();
            $table->decimal('ticket_amount', 15, 2)->nullable();
            $table->string('ticket_attachment')->nullable();

            // Accommodation Booking
            $table->string('accommodation_currency', 10)->nullable();
            $table->decimal('accommodation_amount', 15, 2)->nullable();
            $table->string('accommodation_attachment')->nullable();

            // Final E-Ticket
            $table->string('e_ticket_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_order_bookings');
    }
};