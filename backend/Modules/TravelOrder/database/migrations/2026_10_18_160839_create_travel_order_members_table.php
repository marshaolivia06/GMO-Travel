<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_order_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_order_id')
                ->constrained('travel_orders')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('department_id')
                ->constrained('departments')
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(['travel_order_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_order_members');
    }
};