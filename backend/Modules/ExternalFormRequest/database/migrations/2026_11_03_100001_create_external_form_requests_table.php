<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_form_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // airplane_ferry | hotel_reservation
            $table->string('status')->default('draft');
            $table->string('document_number')->nullable();
            $table->string('transport_mode')->nullable(); // Airplane | Ferry (airplane_ferry saja)
            $table->string('vendor_customer')->nullable();
            $table->text('purpose')->nullable();
            $table->string('trip_type')->nullable(); // one_way | round_trip (airplane_ferry saja)
            $table->string('travel_from')->nullable();
            $table->date('departure_date')->nullable();
            $table->string('travel_to')->nullable();
            $table->date('return_date')->nullable();
            $table->date('check_in_date')->nullable();
            $table->date('check_out_date')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_form_requests');
    }
};
