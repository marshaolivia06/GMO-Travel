<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_form_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_form_request_id')->constrained('external_form_requests')->cascadeOnDelete();
            $table->unsignedInteger('step')->default(0);
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('label')->nullable();
            $table->string('action_type', 20)->default('approve');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // submitted | pending | approved | reviewed
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->index(['external_form_request_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_form_approvals');
    }
};
