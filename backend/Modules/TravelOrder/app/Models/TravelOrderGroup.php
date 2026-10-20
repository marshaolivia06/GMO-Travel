<?php

namespace Modules\TravelOrder\Models; 

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\UserManagement\Models\User;

class TravelOrderGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'trip_name',
        'travel_region',
        'travel_from',
        'travel_to',
        'departure_date',
        'return_date',
        'purpose',
        'status',
        'created_by',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
    return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TravelOrderGroupMember::class);
    }

    public function travelOrders(): HasMany
    {
        return $this->hasMany(TravelOrder::class);
    }
}