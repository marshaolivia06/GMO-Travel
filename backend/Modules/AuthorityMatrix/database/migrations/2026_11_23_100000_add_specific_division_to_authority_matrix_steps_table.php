<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authority_matrix_steps', function (Blueprint $table) {
            $table->boolean('is_specific_division')->default(false)->after('is_specific_department');
            $table->foreignId('division_id')->nullable()->after('department_id')->constrained('master_division')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('authority_matrix_steps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('division_id');
            $table->dropColumn('is_specific_division');
        });
    }
};
