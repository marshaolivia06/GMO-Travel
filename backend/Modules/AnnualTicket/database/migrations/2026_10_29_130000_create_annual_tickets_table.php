<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->year('quota_year');
            $table->enum('route_condition', ['Same Route', 'Different Route']);
            $table->enum('ticket_arrangement', ['Direct Payment', 'Booked by Company']);
            $table->string('status', 60)->default('awaiting_check_hr_admin');
            $table->text('remarks')->nullable();
            $table->string('evidence_path');
            $table->timestamps();

            $table->index(['employee_id', 'quota_year']);
            $table->index('status');
        });

        Schema::create('annual_ticket_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annual_ticket_id')->constrained('annual_tickets')->cascadeOnDelete();
            $table->enum('traveler_type', ['employee', 'dependent']);
            $table->foreignId('traveler_id')->nullable()->constrained('master_dependents')->nullOnDelete();
            $table->string('name', 150);
            $table->string('relationship', 50)->nullable();
            $table->enum('direction', ['departure', 'return']);
            $table->date('travel_date');
            $table->string('travel_from', 150);
            $table->string('travel_to', 150);
            $table->timestamps();

            $table->index(['annual_ticket_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_ticket_legs');
        Schema::dropIfExists('annual_tickets');
    }
};
