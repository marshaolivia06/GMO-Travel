<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annual_tickets', function (Blueprint $table) {
            $table->string('document_number')->nullable()->unique()->after('status');
        });

        // Backfill tiket lama: AT-<Ym dari created_at>-<urutan per bulan>
        $counters = [];

        foreach (DB::table('annual_tickets')->whereNull('document_number')->orderBy('id')->get(['id', 'created_at']) as $row) {
            $prefix = 'AT-' . Carbon::parse($row->created_at)->format('Ym') . '-';
            $counters[$prefix] = ($counters[$prefix] ?? 0) + 1;

            DB::table('annual_tickets')->where('id', $row->id)->update([
                'document_number' => $prefix . str_pad((string) $counters[$prefix], 4, '0', STR_PAD_LEFT),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('annual_tickets', function (Blueprint $table) {
            $table->dropColumn('document_number');
        });
    }
};
