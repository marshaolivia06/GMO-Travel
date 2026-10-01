<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->string('travel_from')->nullable()->change();
            $table->string('travel_to')->nullable()->change();
            $table->date('departure_date')->nullable()->change();
            $table->time('departure_time')->nullable()->change();
            $table->date('return_date')->nullable()->change();
            $table->time('return_time')->nullable()->change();
            $table->text('purpose')->nullable()->change();
            $table->string('travel_region')->nullable()->change();
            $table->string('ferry_ticket_type')->nullable()->change();
            $table->string('ferry_arrangement')->nullable()->change();
            $table->string('accommodation_arrangement')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->string('travel_from')->nullable(false)->change();
            $table->string('travel_to')->nullable(false)->change();
            $table->date('departure_date')->nullable(false)->change();
            $table->time('departure_time')->nullable(false)->change();
            $table->date('return_date')->nullable(false)->change();
            $table->time('return_time')->nullable(false)->change();
            $table->text('purpose')->nullable(false)->change();
            $table->string('travel_region')->nullable(false)->change();
            $table->string('ferry_ticket_type')->nullable(false)->change();
            $table->string('ferry_arrangement')->nullable(false)->change();
            $table->string('accommodation_arrangement')->nullable(false)->change();
        });
    }
};
