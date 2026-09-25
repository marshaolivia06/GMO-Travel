<?php

namespace Modules\TravelOrder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\MasterManagement\Models\Department;
use Modules\TravelOrder\Models\TravelOrderAdvance;
use Modules\UserManagement\Models\User;

class TravelOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'trip_type',
        'status',
        'user_id',
        'department_id',
        'travel_from',
        'travel_to',
        'departure_date',
        'departure_time',
        'return_date',
        'return_time',
        'purpose',
        'remarks',
        'travel_region',
        'ferry_ticket_type',
        'ferry_arrangement',
        'accommodation_arrangement',
        'created_by',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function advance(): HasOne
    {
        return $this->hasOne(TravelOrderAdvance::class);
    }
}