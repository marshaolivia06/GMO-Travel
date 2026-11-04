<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_ticket_bookings', function (Blueprint $table) {
            $table->bigIncrements('annual_ticket_booking_id');
            $table->foreignId('request_id')->constrained('annual_tickets')->cascadeOnDelete();
            $table->string('amount_currency', 10)->default('IDR');
            $table->decimal('amount', 15, 2);
            $table->string('e_ticket_path');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_ticket_bookings');
    }
};
