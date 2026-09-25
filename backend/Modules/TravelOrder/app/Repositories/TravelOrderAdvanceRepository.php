<?php

namespace Modules\TravelOrder\Repositories;

use Modules\TravelOrder\Models\TravelOrderAdvance;

class TravelOrderAdvanceRepository
{
    public function create(array $data): TravelOrderAdvance
    {
        return TravelOrderAdvance::create($data);
    }
}
