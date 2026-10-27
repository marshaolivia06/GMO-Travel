<?php

namespace Modules\TravelOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterNoDailyAllowanceDestination extends Model
{
    protected $fillable = ['region_id', 'name'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(MasterRegion::class, 'region_id');
    }
}