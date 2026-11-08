<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_ticket_emails', function (Blueprint $table) {
            $table->id();

            $table->foreignId('annual_ticket_id')
                ->constrained('annual_tickets')
                ->cascadeOnDelete();

            $table->foreignId('sent_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('to');
            $table->text('cc')->nullable();
            $table->string('subject');
            $table->longText('message');

            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->index(['annual_ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_ticket_emails');
    }
};
