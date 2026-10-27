<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterRegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'coverage' => $this->coverage,
            'currency' => $this->currency,
            'travel_area_label' => $this->travel_area_label,
            'destinations' => $this->whenLoaded('destinations', fn () => $this->destinations->pluck('name')->values()),
            'created_at' => $this->created_at?->toDateString(),
            'updated_at' => $this->updated_at?->toDateString(),
        ];
    }
}