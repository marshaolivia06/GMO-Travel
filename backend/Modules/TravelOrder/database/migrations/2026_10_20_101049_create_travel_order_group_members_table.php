<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_order_group_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('travel_order_group_id')
                ->constrained('travel_order_groups')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['travel_order_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_order_group_members');
    }
};
