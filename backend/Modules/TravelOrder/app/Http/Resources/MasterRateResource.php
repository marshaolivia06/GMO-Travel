<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $daily = $this->daily_allowance !== null ? (float) $this->daily_allowance : null;

        return [
            'id' => $this->id,
            'region_id' => $this->region_id,
            'region' => $this->whenLoaded('region', fn () => ['code' => $this->region->code, 'name' => $this->region->name, 'currency' => $this->region->currency]),
            'grade_min' => $this->grade_min,
            'grade_max' => $this->grade_max,
            'hotel_star' => $this->hotel_star,
            'hotel_rate' => (float) $this->hotel_rate,
            'hotel_rate_currency' => $this->hotel_rate_currency,
            'meal_basis' => $this->meal_basis,
            'meal_limit_per_meal' => $this->meal_limit_per_meal !== null ? (float) $this->meal_limit_per_meal : null,
            'daily_allowance' => $daily,
            'daily_allowance_max_days' => $this->daily_allowance_max_days,
            'daily_allowance_max_per_trip' => $daily !== null ? $daily * $this->daily_allowance_max_days : null,
            'flight_class' => $this->flight_class,
            'effective_from' => $this->effective_from?->toDateString(),
            'effective_to' => $this->effective_to?->toDateString(),
            'created_at' => $this->created_at?->toDateString(),
            'updated_at' => $this->updated_at?->toDateString(),
        ];
    }
}