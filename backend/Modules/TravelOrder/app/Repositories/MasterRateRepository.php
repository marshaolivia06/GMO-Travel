<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Repositories;

use Illuminate\Support\Collection;
use Modules\TravelOrder\Models\{MasterNoDailyAllowanceDestination, MasterRate, MasterRegion};

class MasterRateRepository
{
    public function getRegions(): Collection
    {
        return MasterRegion::query()->orderBy('id')->get(['id', 'code', 'name', 'currency', 'travel_area_label']);
    }

    public function findRegion(int $regionId): ?MasterRegion
    {
        return MasterRegion::find($regionId);
    }

    public function findActiveRate(int $regionId, int $grade, string $date): ?MasterRate
    {
        return MasterRate::query()->where('region_id', $regionId)->where('grade_min', '<=', $grade)->where('grade_max', '>=', $grade)->whereDate('effective_from', '<=', $date)->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))->orderByDesc('effective_from')->first();
    }

    public function isNoDailyAllowanceDestination(int $regionId, ?string $destination): bool
    {
        if (blank($destination)) return false;

        return MasterNoDailyAllowanceDestination::query()->where('region_id', $regionId)->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($destination))])->exists();
    }
}