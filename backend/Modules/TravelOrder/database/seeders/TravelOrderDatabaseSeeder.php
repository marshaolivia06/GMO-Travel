<?php

namespace Modules\TravelOrder\Database\Seeders;

use Illuminate\Database\Seeder;

class TravelOrderDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MasterRegionSeeder::class,
            MasterRateSeeder::class,
        ]);
    }
}