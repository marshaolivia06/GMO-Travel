<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_form_approvals', function (Blueprint $table) {
            $table->unsignedInteger('round')->default(1)->after('external_form_request_id');

            $table->index(['external_form_request_id', 'round', 'step'], 'external_form_approvals_round_index');
        });
    }

    public function down(): void
    {
        Schema::table('external_form_approvals', function (Blueprint $table) {
            $table->dropIndex(['external_form_request_id', 'round', 'step']);
            $table->dropColumn('round');
        });
    }
};
