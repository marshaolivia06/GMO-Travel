<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_division', function (Blueprint $table) {
            $table->foreignId('division_head_id')
                ->nullable()
                ->after('division_name')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('master_division', function (Blueprint $table) {
            $table->dropForeign(['division_head_id']);
            $table->dropColumn('division_head_id');
        });
    }
};