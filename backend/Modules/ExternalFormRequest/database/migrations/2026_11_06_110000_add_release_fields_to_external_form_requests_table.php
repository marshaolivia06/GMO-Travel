<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_form_requests', function (Blueprint $table) {
            $table->string('departure_booking_code')->nullable()->after('departure_ata');
            $table->string('return_booking_code')->nullable()->after('return_ata');
            $table->string('reservation_confirmation_no')->nullable()->after('room_type');
        });

        Schema::create('external_form_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_form_request_id')->constrained('external_form_requests')->cascadeOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('to');
            $table->text('cc')->nullable();
            $table->string('subject');
            $table->longText('message');
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_form_emails');

        Schema::table('external_form_requests', function (Blueprint $table) {
            $table->dropColumn(['departure_booking_code', 'return_booking_code', 'reservation_confirmation_no']);
        });
    }
};
