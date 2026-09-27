<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Repositories;

use Modules\TravelOrder\Models\TravelAdvanceMaster;

class TravelAdvanceRepository
{
    public function getAll()
    {
        return TravelAdvanceMaster::query()
            ->where('status', 1)
            ->orderBy('id_travel_advance_master', 'desc')
            ->get();
    }

    public function findById(int $id): ?TravelAdvanceMaster
    {
        return TravelAdvanceMaster::query()
            ->where('id_travel_advance_master', $id)
            ->where('status', 1)
            ->first();
    }

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

    public function create(array $data): TravelAdvanceMaster
    {
        return TravelAdvanceMaster::create($data);
    }

    public function update(int $id, array $data): ?TravelAdvanceMaster
    {
        $master = $this->findById($id);

        if (! $master) {
            return null;
        }

        $master->update($data);

        return $master->fresh();
    }

    public function delete(int $id): bool
    {
        $master = $this->findById($id);

        if (! $master) {
            return false;
        }

        return $master->update([
            'status' => 0,
        ]);
    }
}