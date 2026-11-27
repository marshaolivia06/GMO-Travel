<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris per item pengeluaran (breakfast, transport 1, other 2, ...) menunjuk day_number
        Schema::create('travel_expense_report_expenses', function (Blueprint $table) {
            $table->id();
            // Nama FK eksplisit - nama default melebihi batas 64 karakter MySQL
            $table->unsignedBigInteger('travel_expense_report_id');
            $table->foreign('travel_expense_report_id', 'ter_expenses_report_foreign')->references('id')->on('travel_expense_reports')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');
            $table->string('category', 40);
            // Urutan item dalam satu hari untuk kategori multi-entry (transport 1, transport 2, ...)
            $table->unsignedTinyInteger('item_index')->default(1);

            $table->string('kind', 40)->nullable();
            $table->string('description', 255)->nullable();
            $table->string('currency', 10)->default('IDR');
            $table->decimal('actual_amount', 15, 2)->nullable();
            $table->decimal('eligible_amount', 15, 2)->nullable();
            $table->boolean('manual_review')->default(false);

            // Payment arrangement (fitur grup): myself / paid_by_other / pay_for_others
            $table->string('payment_mode', 20)->default('myself');
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('paid_for')->nullable();

            // Field spesifik kategori - hanya terisi sesuai kategori
            $table->string('dinner_type', 40)->nullable();
            $table->text('justification')->nullable();
            $table->string('hotel_name', 255)->nullable();
            $table->string('room_type', 255)->nullable();
            $table->string('hotel_standard', 40)->nullable();

            // Struk per item (receipt-based)
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->timestamps();

            $table->index(['travel_expense_report_id', 'day_number'], 'ter_expenses_report_day_index');
            $table->index('category', 'ter_expenses_category_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_expense_report_expenses');
    }
};
