<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Modules\TravelOrder\Models\MasterRate;

class MasterRateService
{
    public function getAll(?int $regionId = null): Collection
    {
        return MasterRate::with('region')
            ->when($regionId, fn ($query, $id) => $query->where('region_id', $id))
            ->orderBy('region_id')
            ->orderBy('grade_min')
            ->orderByDesc('effective_from')
            ->get();
    }

    public function findById(int $id): MasterRate
    {
        return MasterRate::with('region')->findOrFail($id);
    }

    public function create(array $data): MasterRate
    {
        $data = $this->normalize($data);
        $this->assertNoOverlap($data);

        return MasterRate::create($data)->load('region');
    }

    public function update(int $id, array $data): MasterRate
    {
        $rate = MasterRate::findOrFail($id);
        $data = $this->normalize($data);
        $this->assertNoOverlap($data, $id);
        $rate->update($data);

        return $rate->load('region');
    }

    public function delete(int $id): void
    {
        MasterRate::findOrFail($id)->delete();
    }

    private function normalize(array $data): array
    {
        if ($data['meal_basis'] === 'at_cost') {
            $data['meal_limit_per_meal'] = null;
        }

        if (($data['daily_allowance'] ?? null) === null) {
            $data['daily_allowance'] = null;
            $data['daily_allowance_max_days'] = null;
        }

        return $data;
    }

    private function assertNoOverlap(array $data, ?int $ignoreId = null): void
    {
        $overlap = MasterRate::where('region_id', $data['region_id'])
            ->when($ignoreId, fn ($query, $id) => $query->where('id', '!=', $id))
            ->where('grade_min', '<=', $data['grade_max'])
            ->where('grade_max', '>=', $data['grade_min'])
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $data['effective_from']))
            ->when($data['effective_to'] ?? null, fn ($query, $to) => $query->where('effective_from', '<=', $to))
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages(['grade_min' => 'The grade range overlaps another rate in the same region and effective period.']);
        }
    }
}