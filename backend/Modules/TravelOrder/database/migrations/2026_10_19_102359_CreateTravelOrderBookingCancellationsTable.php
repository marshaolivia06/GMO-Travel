<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_order_booking_cancellations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('travel_order_id')
                ->constrained('travel_orders')
                ->cascadeOnDelete();

            $table->foreignId('booking_id')
                ->nullable()
                ->constrained('travel_order_bookings')
                ->nullOnDelete();

            $table->string('item_type', 30);
            $table->text('reason');
            $table->string('refund_status', 50)->default('Pending Refund');
            $table->string('currency', 10)->nullable();
            $table->decimal('original_amount', 15, 2)->nullable();
            $table->decimal('refund_amount', 15, 2)->nullable();
            $table->decimal('non_refundable_amount', 15, 2)->nullable();
            $table->string('confirmation_file')->nullable();

            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(
                ['travel_order_id', 'item_type'],
                'toc_cancel_order_item_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_order_booking_cancellations');
    }
};