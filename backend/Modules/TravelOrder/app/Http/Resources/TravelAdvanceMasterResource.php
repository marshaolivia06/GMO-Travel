<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TravelAdvanceMasterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_travel_advance_master' => $this->id_travel_advance_master,
            'travel_region' => $this->travel_region,
            'currency' => $this->currency,
            'pocket_money_limit' => (float) $this->pocket_money_limit,
            'meal_allowance_limit' => (float) $this->meal_allowance_limit,
            'status' => (bool) $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}