<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Services;

use Modules\TravelOrder\Models\{MasterNoDailyAllowanceDestination, MasterRate, MasterRegion};

class MasterRateLimitService
{
    private const MEALS_PER_DAY = 3;

    public function getLimits(int $regionId, ?string $destination, int $grade, int $days, string $date): ?array
    {
        $region = MasterRegion::find($regionId);
        $rate = $region ? MasterRate::query()->where('region_id', $regionId)->where('grade_min', '<=', $grade)->where('grade_max', '>=', $grade)->whereDate('effective_from', '<=', $date)->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))->orderByDesc('effective_from')->first() : null;

        if (! $rate) return null;

        $days = max($days, 1);
        $pocketDays = min($days, (int) ($rate->daily_allowance_max_days ?: $days));
        $noDaily = filled($destination) && MasterNoDailyAllowanceDestination::query()->where('region_id', $regionId)->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($destination))])->exists();
        $mealUnlimited = $rate->meal_basis === 'at_cost';

        return ['region_id' => $regionId, 'currency' => $region->currency, 'days' => $days, 'pocket_money_limit' => $noDaily ? 0.0 : (float) $rate->daily_allowance * $pocketDays, 'meal_basis' => $rate->meal_basis, 'meal_limit_per_meal' => $mealUnlimited ? null : (float) $rate->meal_limit_per_meal, 'meal_allowance_limit' => $mealUnlimited ? null : (float) $rate->meal_limit_per_meal * self::MEALS_PER_DAY * $days];
    }

    public function validateAdvance(int $regionId, ?string $destination, float $pocketMoney, float $mealAllowance, int $grade, int $days, string $date): array
    {
        $limits = $this->getLimits($regionId, $destination, $grade, $days, $date);

        if (! $limits) return ['valid' => false, 'message' => 'Master Rate data not found.'];

        if ($pocketMoney > $limits['pocket_money_limit']) return ['valid' => false, 'message' => 'Pocket Money exceeds the allowed limit.'];

        if ($limits['meal_allowance_limit'] !== null && $mealAllowance > $limits['meal_allowance_limit']) return ['valid' => false, 'message' => 'Meal Allowance exceeds the allowed limit.'];

        return ['valid' => true, 'message' => null];
    }
}