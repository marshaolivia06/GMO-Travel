<?php

namespace Modules\TravelOrder\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\TravelOrder\Models\MasterRegion;

class MasterRegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['code' => 'DN', 'name' => 'Domestik', 'coverage' => 'Seluruh kota di Indonesia', 'currency' => 'IDR', 'travel_area_label' => null],
            ['code' => 'LN-SG', 'name' => 'Singapore', 'coverage' => 'Singapore', 'currency' => 'SGD', 'travel_area_label' => null],
            ['code' => 'LN-A1', 'name' => 'Area I', 'coverage' => 'Asia (di luar Singapore & Jepang), Australia, New Zealand', 'currency' => 'USD', 'travel_area_label' => 'Asia (diluar Singapore dan Jepang), Australia, New Zealand'],
            ['code' => 'LN-A2-US', 'name' => 'Area II', 'coverage' => 'Jepang, Amerika', 'currency' => 'USD', 'travel_area_label' => 'Jepang, Amerika'],
            ['code' => 'LN-A2-EU', 'name' => 'Area II', 'coverage' => 'Eropa', 'currency' => 'EUR', 'travel_area_label' => 'Eropa'],
        ];

        foreach ($regions as $region) {
            MasterRegion::updateOrCreate(['code' => $region['code']], $region);
        }
    }
}