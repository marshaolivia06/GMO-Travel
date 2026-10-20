
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_order_groups', function (Blueprint $table) {
            $table->id();
            $table->string('trip_name');
            $table->string('travel_region');
            $table->string('travel_from');
            $table->string('travel_to');
            $table->date('departure_date');
            $table->date('return_date');
            $table->text('purpose')->nullable();
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_order_groups');
    }
};
