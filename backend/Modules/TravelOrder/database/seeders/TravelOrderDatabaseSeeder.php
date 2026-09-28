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
                'travel_region' => 'Overseas',
                'currency' => $currency,
                'pocket_money_limit' => 100,
                'meal_allowance_limit' => 150,
                'status' => true,
            ]);
        }

        $domesticGrades = [
            ['grade_min' => 1,  'grade_max' => 11, 'meal_allowance_limit' => 35000, 'pocket_money_limit' => 50000],
            ['grade_min' => 12, 'grade_max' => 15, 'meal_allowance_limit' => 45000, 'pocket_money_limit' => 60000],
            ['grade_min' => 16, 'grade_max' => 17, 'meal_allowance_limit' => 55000, 'pocket_money_limit' => 70000],
            ['grade_min' => 18, 'grade_max' => 19, 'meal_allowance_limit' => 0,     'pocket_money_limit' => 80000],
            ['grade_min' => 20, 'grade_max' => 20, 'meal_allowance_limit' => 0,     'pocket_money_limit' => 90000],
            ['grade_min' => 21, 'grade_max' => 22, 'meal_allowance_limit' => 0,     'pocket_money_limit' => 100000],
        ];

        foreach ($domesticGrades as $row) {
            TravelAdvanceMaster::create([
                'travel_region' => 'Domestic',
                'country' => 'Indonesia',
                'grade_min' => $row['grade_min'],
                'grade_max' => $row['grade_max'],
                'currency' => 'IDR',
                'pocket_money_limit' => $row['pocket_money_limit'],
                'meal_allowance_limit' => $row['meal_allowance_limit'],
                'status' => true,
            ]);
        }
    }
}