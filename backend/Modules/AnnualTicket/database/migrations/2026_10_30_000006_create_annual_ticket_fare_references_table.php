<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_ticket_fare_references', function (Blueprint $table) {
            $table->bigIncrements('fare_reference_id');
            $table->foreignId('request_id')->constrained('annual_tickets')->cascadeOnDelete();
            $table->enum('direction', ['departure', 'return']);
            $table->string('operator_name', 150);
            $table->decimal('fare_amount', 15, 2);
            $table->string('currency', 10)->default('IDR');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_ticket_fare_references');
    }
};
