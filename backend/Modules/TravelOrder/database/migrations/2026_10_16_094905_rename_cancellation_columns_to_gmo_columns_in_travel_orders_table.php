<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->renameColumn('cancellation_remark', 'gmo_remark');
            $table->renameColumn('cancellation_evidence', 'gmo_evidence');
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->renameColumn('gmo_remark', 'cancellation_remark');
            $table->renameColumn('gmo_evidence', 'cancellation_evidence');
        });
    }
};