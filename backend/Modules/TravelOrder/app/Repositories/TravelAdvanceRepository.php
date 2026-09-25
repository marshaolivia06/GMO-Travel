<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Repositories;

use Modules\TravelOrder\Models\TravelAdvanceMaster;

class TravelAdvanceRepository
{
    public function findByRegionAndCurrency(
        string $travelRegion,
        string $currency
    ): ?TravelAdvanceMaster {
        return TravelAdvanceMaster::query()
            ->where('travel_region', $travelRegion)
            ->where('currency', $currency)
            ->where('status', 1)
            ->first();
    }
}
