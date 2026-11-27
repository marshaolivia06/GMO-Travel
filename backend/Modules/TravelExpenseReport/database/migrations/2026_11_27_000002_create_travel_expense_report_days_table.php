<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris per hari perjalanan (Day 1, Day 2, ...) - konteks untuk aturan per-hari
        Schema::create('travel_expense_report_days', function (Blueprint $table) {
            $table->id();
            // Nama FK eksplisit - nama default melebihi batas 64 karakter MySQL
            $table->unsignedBigInteger('travel_expense_report_id');
            $table->foreign('travel_expense_report_id', 'ter_days_report_foreign')->references('id')->on('travel_expense_reports')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');
            $table->date('date');
            $table->string('type', 20)->default('stay');
            $table->string('effective_time', 10)->nullable();
            // Konteks hari: melewati lebih dari satu negara dalam sehari -> manual OC review
            $table->boolean('different_countries')->default(false);
            $table->timestamps();

            $table->unique(['travel_expense_report_id', 'day_number'], 'ter_days_report_day_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_expense_report_days');
    }
};
