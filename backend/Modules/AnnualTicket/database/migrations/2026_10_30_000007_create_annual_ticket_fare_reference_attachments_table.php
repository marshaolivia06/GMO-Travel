<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_ticket_fare_reference_attachments', function (Blueprint $table) {
            $table->bigIncrements('fare_reference_attachment_id');
            $table->foreignId('request_id')->constrained('annual_tickets')->cascadeOnDelete();
            $table->string('file_path');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_ticket_fare_reference_attachments');
    }
};
