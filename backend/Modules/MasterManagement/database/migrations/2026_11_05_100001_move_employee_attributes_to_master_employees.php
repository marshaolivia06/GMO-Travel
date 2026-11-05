<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pindahkan data atribut employee dari tabel users ke master_employees
        DB::statement(<<<'SQL'
            INSERT INTO master_employees (user_id, grade, eligible_destination_id, date_of_join, office_location, created_at, updated_at)
            SELECT id, grade, eligible_destination_id, date_of_join, office_location, NOW(), NOW()
            FROM users
            WHERE grade IS NOT NULL
               OR eligible_destination_id IS NOT NULL
               OR date_of_join IS NOT NULL
               OR office_location IS NOT NULL
        SQL);

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['eligible_destination_id']);
            $table->dropColumn(['grade', 'eligible_destination_id', 'date_of_join', 'office_location']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('grade')->nullable()->after('status');
            $table->foreignId('eligible_destination_id')
                ->nullable()
                ->after('grade')
                ->constrained('master_destinations')
                ->nullOnDelete();
            $table->date('date_of_join')->nullable()->after('eligible_destination_id');
            $table->string('office_location', 100)->nullable()->after('date_of_join');
        });

        // Kembalikan data dari master_employees ke users
        DB::statement(<<<'SQL'
            UPDATE users u
            JOIN master_employees me ON me.user_id = u.id
            SET u.grade = me.grade,
                u.eligible_destination_id = me.eligible_destination_id,
                u.date_of_join = me.date_of_join,
                u.office_location = me.office_location
        SQL);
    }
};
