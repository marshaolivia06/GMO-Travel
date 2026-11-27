<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_expense_reports', function (Blueprint $table) {
            // TER berbasis Travel Order (individu & grup) - satu TER per TO
            $table->foreignId('travel_order_id')->nullable()->constrained('travel_orders')->nullOnDelete();
            $table->unique('travel_order_id');
            $table->index('travel_order_id');

            // Snapshot travel advance dari TO (untuk settlement)
            $table->decimal('advance_meal_allowance', 15, 2)->nullable();
            $table->decimal('advance_pocket_money', 15, 2)->nullable();

            // Total eligible & actual yang dihitung dari rincian expense saat submit
            $table->decimal('total_eligible', 15, 2)->nullable();
        });

        // claim_type untuk TER travel order (rincian per hari/kategori)
        DB::statement("ALTER TABLE travel_expense_reports MODIFY claim_type ENUM('ticket_reimbursement', 'excess_settlement', 'expense_claim') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE travel_expense_reports MODIFY claim_type ENUM('ticket_reimbursement', 'excess_settlement') NOT NULL");

        Schema::table('travel_expense_reports', function (Blueprint $table) {
            $table->dropUnique(['travel_order_id']);
            $table->dropForeign(['travel_order_id']);
            $table->dropColumn(['travel_order_id', 'advance_meal_allowance', 'advance_pocket_money', 'total_eligible']);
        });
    }
};
