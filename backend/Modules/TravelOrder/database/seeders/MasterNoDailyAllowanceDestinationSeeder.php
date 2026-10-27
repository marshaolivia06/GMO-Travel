<?php

namespace Modules\TravelOrder\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\TravelOrder\Models\MasterNoDailyAllowanceDestination;
use Modules\TravelOrder\Models\MasterRegion;

class MasterNoDailyAllowanceDestinationSeeder extends Seeder
{
    public function run(): void
    {
        $regionIds = MasterRegion::pluck('id', 'code');

        $destinations = [['Batam', 'DN'], ['Medan', 'DN'], ['Jakarta', 'DN'], ['Singapore', 'LN-SG']];

        foreach ($destinations as [$name, $code]) {
            MasterNoDailyAllowanceDestination::updateOrCreate(['region_id' => $regionIds[$code], 'name' => $name]);
        }
    }
}