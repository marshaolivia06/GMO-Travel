<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('grade')->nullable();
            $table->foreignId('eligible_destination_id')
                ->nullable()
                ->constrained('master_destinations')
                ->nullOnDelete();
            $table->date('date_of_join')->nullable();
            $table->string('office_location', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_employees');
    }
};
