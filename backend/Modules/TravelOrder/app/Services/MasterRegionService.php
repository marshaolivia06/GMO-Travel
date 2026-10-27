<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\TravelOrder\Models\MasterRate;
use Modules\TravelOrder\Models\MasterRegion;

class MasterRegionService
{
    public function getAll(): Collection
    {
        return MasterRegion::with('destinations')->orderBy('id')->get();
    }

    public function findById(int $id): MasterRegion
    {
        return MasterRegion::with('destinations')->findOrFail($id);
    }

    public function create(array $data): MasterRegion
    {
        return DB::transaction(function () use ($data) {
            $destinations = $data['destinations'] ?? [];
            unset($data['destinations']);

            $region = MasterRegion::create($data);
            $this->syncDestinations($region, $destinations);

            return $region->load('destinations');
        });
    }

    public function update(int $id, array $data): MasterRegion
    {
        return DB::transaction(function () use ($id, $data) {
            $region = MasterRegion::findOrFail($id);
            $hasDestinations = array_key_exists('destinations', $data);
            $destinations = $data['destinations'] ?? [];
            unset($data['destinations']);

            $region->update($data);

            if ($hasDestinations) {
                $this->syncDestinations($region, $destinations);
            }

            return $region->load('destinations');
        });
    }

    public function hasRates(int $id): bool
    {
        return MasterRate::where('region_id', $id)->exists();
    }

    public function delete(int $id): void
    {
        MasterRegion::findOrFail($id)->delete();
    }

    private function syncDestinations(MasterRegion $region, array $names): void
    {
        $region->destinations()->whereNotIn('name', $names)->delete();

        foreach ($names as $name) {
            $region->destinations()->firstOrCreate(['name' => $name]);
        }
    }
}