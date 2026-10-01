<?php

namespace Modules\TravelOrder\Repositories;

use Modules\TravelOrder\Models\TravelOrderAdvance;

class TravelOrderAdvanceRepository
{
    public function create(array $data): TravelOrderAdvance
    {
        return TravelOrderAdvance::create($data);
    }

    public function findByTravelOrderId(int $travelOrderId): ?TravelOrderAdvance
    {
        return TravelOrderAdvance::where(
            'travel_order_id',
            $travelOrderId
        )->first();
    }

    public function update(
        int $id,
        array $data
    ): TravelOrderAdvance {
        $advance = TravelOrderAdvance::findOrFail($id);

        $advance->update($data);

        return $advance->fresh();
    }
}
