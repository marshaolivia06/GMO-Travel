<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rantai approval TER mengikuti authority matrix "Travel Expense Report"
        Schema::create('travel_expense_report_approvals', function (Blueprint $table) {
            $table->id();
            // Nama FK eksplisit - nama default melebihi batas 64 karakter MySQL
            $table->unsignedBigInteger('travel_expense_report_id');
            $table->foreign('travel_expense_report_id', 'ter_approvals_report_foreign')->references('id')->on('travel_expense_reports')->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence');
            $table->foreignId('authority_matrix_step_id')->nullable()->constrained('authority_matrix_steps')->nullOnDelete();
            $table->string('action_type', 20)->nullable();
            $table->foreignId('role_id')->constrained('roles');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->unique(['travel_expense_report_id', 'sequence'], 'ter_approvals_report_sequence_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_expense_report_approvals');
    }
};
