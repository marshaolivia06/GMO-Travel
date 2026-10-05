<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authority_matrix_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('authority_matrix_id')->constrained('authority_matrices')->cascadeOnDelete();
            $table->unsignedTinyInteger('step');
            $table->foreignId('role_id')->constrained('roles');
            $table->string('label');
            $table->unsignedTinyInteger('role_level')->nullable();
            $table->boolean('is_specific_section')->default(false);
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->boolean('is_specific_department')->default(false);
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authority_matrix_steps');
    }
};