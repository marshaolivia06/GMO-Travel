<?php

namespace Modules\TravelOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterRegion extends Model
{
    protected $fillable = ['code', 'name', 'coverage', 'currency', 'travel_area_label'];

    public function destinations(): HasMany
    {
        return $this->hasMany(MasterNoDailyAllowanceDestination::class, 'region_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(MasterRate::class, 'region_id');
    }
}