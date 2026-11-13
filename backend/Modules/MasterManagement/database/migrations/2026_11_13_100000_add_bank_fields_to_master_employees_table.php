<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rekening employee untuk keperluan transfer reimbursement di Travel Expense Report
        // (account holder tidak disimpan - selalu pakai nama employee di tabel users)
        Schema::table('master_employees', function (Blueprint $table) {
            $table->string('bank_name', 100)->nullable()->after('office_location');
            $table->string('bank_account_number', 50)->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('master_employees', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_number']);
        });
    }
};
