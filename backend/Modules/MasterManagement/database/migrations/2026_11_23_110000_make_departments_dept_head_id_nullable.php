<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Dept Head departemen boleh kosong (mis. Departemen GMO belum punya Dept Head)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dept_head_id');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('dept_head_id')
                ->nullable()
                ->after('category_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dept_head_id');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('dept_head_id')
                ->after('category_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }
};
