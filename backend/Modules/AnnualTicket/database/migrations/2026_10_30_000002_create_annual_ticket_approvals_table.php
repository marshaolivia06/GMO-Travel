<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_ticket_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annual_ticket_id')->constrained('annual_tickets')->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence');
            $table->foreignId('authority_matrix_step_id')->nullable()->constrained('authority_matrix_steps')->nullOnDelete();
            $table->string('action_type', 20)->default('approve');
            $table->foreignId('role_id')->constrained('roles');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->unique(['annual_ticket_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_ticket_approvals');
    }
};
