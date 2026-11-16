<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authority_matrix_steps', function (Blueprint $table) {
            $table->boolean('is_specific_supervisor')
                ->default(false)
                ->after('is_specific_section');

            $table->foreignId('supervisor_id')
                ->nullable()
                ->after('is_specific_supervisor')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('area', 100)
                ->nullable()
                ->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('authority_matrix_steps', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn(['is_specific_supervisor', 'supervisor_id', 'area']);
        });
    }
};
