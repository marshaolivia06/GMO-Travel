<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_form_requests', function (Blueprint $table) {
            $table->string('request_type')
                ->default('business_trip')
                ->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('external_form_requests', function (Blueprint $table) {
            $table->dropColumn('request_type');
        });
    }
};
