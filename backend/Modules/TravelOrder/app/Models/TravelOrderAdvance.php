<?php

namespace Modules\TravelOrder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelOrderAdvance extends Model
{
    use HasFactory;

    protected $fillable = [
        'travel_order_id',
        'meal_allowance',
        'pocket_money',
        'currency',
    ];

    protected $casts = [
        'meal_allowance' => 'decimal:2',
        'pocket_money' => 'decimal:2',
    ];

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class);
    }
}
