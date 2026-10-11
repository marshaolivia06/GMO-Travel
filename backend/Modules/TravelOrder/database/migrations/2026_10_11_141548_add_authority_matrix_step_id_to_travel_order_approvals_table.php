
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_order_approvals', function (Blueprint $table) {
            $table->foreignId('authority_matrix_step_id')
                ->nullable()
                ->after('role_id')
                ->constrained('authority_matrix_steps')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('travel_order_approvals', function (Blueprint $table) {
            $table->dropForeign(['authority_matrix_step_id']);
            $table->dropColumn('authority_matrix_step_id');
        });
    }
};
