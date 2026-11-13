<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_expense_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            // Pembeda jenis TER: annual_ticket sekarang, travel_order menyusul nanti
            $table->string('source_type', 60)->default('annual_ticket');
            $table->foreignId('annual_ticket_id')->nullable()->constrained('annual_tickets')->cascadeOnDelete();
            // ticket_reimbursement = Direct Payment (same/different route), excess_settlement = Booked by Company + Different Route
            $table->enum('claim_type', ['ticket_reimbursement', 'excess_settlement']);
            $table->string('status', 60)->default('draft');
            $table->string('document_number')->nullable();
            $table->string('currency', 10)->default('IDR');
            // Snapshot nilai referensi fare (max) saat claim dibuat
            $table->decimal('total_fare_reference', 15, 2)->nullable();
            // Reimbursement (Direct Payment)
            $table->decimal('total_actual_amount', 15, 2)->nullable();
            $table->decimal('settlement_amount', 15, 2)->nullable();
            // Excess settlement (Booked by Company + Different Route)
            $table->decimal('company_booking_amount', 15, 2)->nullable();
            $table->decimal('employee_excess', 15, 2)->nullable();
            $table->string('proof_of_payment_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // Satu TER per Annual Ticket - draft yang sudah ada diupdate, bukan dibuat baru
            $table->unique('annual_ticket_id');
            $table->index(['employee_id', 'status']);
            $table->index('source_type');
        });

        // Lampiran reimbursement: e-ticket + boarding pass + invoice
        Schema::create('travel_expense_report_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('travel_expense_report_id');
            // Nama FK eksplisit - nama default melebihi batas 64 karakter MySQL
            $table->foreign('travel_expense_report_id', 'ter_attachments_report_foreign')->references('id')->on('travel_expense_reports')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_expense_report_attachments');
        Schema::dropIfExists('travel_expense_reports');
    }
};
