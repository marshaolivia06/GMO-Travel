<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();

            $table->foreignId('section_head_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropForeign(['section_head_id']);
            $table->dropColumn('section_head_id');

            $table->string('status')->default('submitted')->change();
        });
    }
};