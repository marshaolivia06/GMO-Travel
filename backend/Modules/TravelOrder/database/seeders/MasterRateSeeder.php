<?php

namespace Modules\TravelOrder\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\TravelOrder\Models\MasterRate;
use Modules\TravelOrder\Models\MasterRegion;

class MasterRateSeeder extends Seeder
{
    public function run(): void
    {
        $regionIds = MasterRegion::pluck('id', 'code');

        $rates = [
            'DN' => [
                'hotel_currency' => 'IDR',
                'source' => 'SK Perjalanan Dinas - Lampiran Dalam Negeri',
                'rows' => [
                    [1, 11, 3, 400000, 'limit', 35000, 50000, 'economy'],
                    [12, 15, 3, 400000, 'limit', 45000, 60000, 'economy'],
                    [16, 17, 3, 400000, 'limit', 55000, 70000, 'economy'],
                    [18, 19, 4, 600000, 'at_cost', null, 80000, 'economy'],
                    [20, 20, 4, 600000, 'at_cost', null, 90000, 'economy'],
                    [21, 22, 5, 800000, 'at_cost', null, 100000, 'economy_business'],
                ],
            ],
            'LN-A1' => [
                'hotel_currency' => 'USD',
                'source' => 'SK Perjalanan Dinas - Lampiran Luar Negeri',
                'rows' => [
                    [1, 15, 3, 100, 'limit', 16, 35, 'economy'],
                    [16, 17, 3, 100, 'limit', 18, 40, 'economy'],
                    [18, 19, 4, 130, 'at_cost', null, 45, 'economy'],
                    [20, 20, 5, 150, 'at_cost', null, 50, 'economy_business'],
                    [21, 22, 5, 160, 'at_cost', null, 55, 'economy_business'],
                ],
            ],
            'LN-A2-US' => [
                'hotel_currency' => 'USD',
                'source' => 'SK Perjalanan Dinas - Lampiran Luar Negeri',
                'rows' => [
                    [1, 15, 3, 130, 'limit', 20, 40, 'economy'],
                    [16, 17, 3, 130, 'limit', 22, 45, 'economy'],
                    [18, 19, 4, 150, 'at_cost', null, 50, 'economy'],
                    [20, 20, 5, 180, 'at_cost', null, 55, 'economy_business'],
                    [21, 22, 5, 200, 'at_cost', null, 60, 'economy_business'],
                ],
            ],
            'LN-A2-EU' => [
                'hotel_currency' => 'USD',
                'source' => 'SK Perjalanan Dinas - Lampiran Luar Negeri',
                'rows' => [
                    [1, 15, 3, 130, 'limit', 18, 30, 'economy'],
                    [16, 17, 3, 130, 'limit', 20, 35, 'economy'],
                    [18, 19, 4, 150, 'at_cost', null, 40, 'economy'],
                    [20, 20, 5, 180, 'at_cost', null, 45, 'economy_business'],
                    [21, 22, 5, 200, 'at_cost', null, 50, 'economy_business'],
                ],
            ],
            'LN-SG' => [
                'hotel_currency' => 'SGD',
                'source' => 'SK Perjalanan Dinas - Lampiran Luar Negeri',
                'rows' => [
                    [1, 15, 3, 120, 'limit', 15, null, 'economy'],
                    [16, 17, 3, 150, 'limit', 20, null, 'economy'],
                    [18, 19, 4, 180, 'at_cost', null, null, 'economy'],
                    [20, 20, 5, 200, 'at_cost', null, null, 'economy_business'],
                    [21, 22, 5, 220, 'at_cost', null, null, 'economy_business'],
                ],
            ],
        ];

        foreach ($rates as $code => $set) {
            foreach ($set['rows'] as [$gradeMin, $gradeMax, $star, $hotelRate, $basis, $mealLimit, $daily, $flightClass]) {
                MasterRate::updateOrCreate(
                    ['region_id' => $regionIds[$code], 'grade_min' => $gradeMin, 'grade_max' => $gradeMax, 'effective_from' => '2026-01-01'],
                    [
                        'hotel_star' => $star,
                        'hotel_rate' => $hotelRate,
                        'hotel_rate_currency' => $set['hotel_currency'],
                        'meal_basis' => $basis,
                        'meal_limit_per_meal' => $mealLimit,
                        'daily_allowance' => $daily,
                        'daily_allowance_max_days' => $daily === null ? null : 20,
                        'flight_class' => $flightClass,
                        'effective_to' => null,
                        'source' => $set['source'],
                    ]
                );
            }
        }
    }
}