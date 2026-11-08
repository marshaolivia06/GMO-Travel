<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_ticket_leg_cancellations', function (Blueprint $table) {
            $table->bigIncrements('leg_cancellation_id');
            $table->foreignId('request_id')->constrained('annual_tickets')->cascadeOnDelete();
            $table->foreignId('leg_id')->constrained('annual_ticket_legs')->cascadeOnDelete();
            $table->text('reason');
            $table->string('refund_status');
            $table->string('currency', 10)->nullable();
            $table->decimal('original_amount', 15, 2);
            $table->decimal('refund_amount', 15, 2)->default(0);
            $table->decimal('non_refundable_amount', 15, 2)->default(0);
            $table->string('confirmation_file')->nullable();
            $table->foreignId('processed_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_ticket_leg_cancellations');
    }
};
