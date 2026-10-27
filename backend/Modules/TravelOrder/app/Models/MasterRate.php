<?php

namespace Modules\TravelOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterRate extends Model
{
    protected $fillable = ['region_id', 'grade_min', 'grade_max', 'hotel_star', 'hotel_rate', 'hotel_rate_currency', 'meal_basis', 'meal_limit_per_meal', 'daily_allowance', 'daily_allowance_max_days', 'flight_class', 'effective_from', 'effective_to', 'source'];

    protected $casts = [
        'hotel_rate' => 'decimal:2',
        'meal_limit_per_meal' => 'decimal:2',
        'daily_allowance' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(MasterRegion::class, 'region_id');
    }
}