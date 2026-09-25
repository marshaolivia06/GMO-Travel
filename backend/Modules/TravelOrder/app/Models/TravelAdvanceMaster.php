<?php

namespace Modules\TravelOrder\Models;

use Illuminate\Database\Eloquent\Model;

class TravelAdvanceMaster extends Model
{
    protected $table = 'travel_advance_masters';

    protected $primaryKey = 'id_travel_advance_master';

    protected $fillable = [
        'travel_region',
        'currency',
        'pocket_money_limit',
        'meal_allowance_limit',
        'status',
    ];
    
    protected $casts = [
        'pocket_money_limit' => 'decimal:2',
        'meal_allowance_limit' => 'decimal:2',
        'status' => 'boolean',
    ];
    
}
