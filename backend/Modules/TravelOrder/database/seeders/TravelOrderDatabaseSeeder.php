<?php

namespace Modules\TravelOrder\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\TravelOrder\Models\TravelAdvanceMaster;

class TravelOrderDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        TravelAdvanceMaster::create([
            'travel_region' => 'Singapore',
            'currency' => 'SGD',
            'pocket_money_limit' => 100,
            'meal_allowance_limit' => 150,
            'status' => true,
        ]);

        foreach (['USD', 'EUR', 'MYR', 'JPY'] as $currency) {
            TravelAdvanceMaster::create([
                'travel_region' => 'Non Singapore',
                'currency' => $currency,
                'pocket_money_limit' => 100,
                'meal_allowance_limit' => 150,
                'status' => true,
            ]);
        }
    }
}
