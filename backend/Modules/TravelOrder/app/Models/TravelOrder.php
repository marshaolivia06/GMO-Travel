<?php

namespace Modules\TravelOrder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\MasterManagement\Models\Department;
use Modules\TravelOrder\Models\TravelOrderAdvance;
use Modules\TravelOrder\Models\TravelOrderApproval;
use Modules\UserManagement\Models\User;
use Modules\TravelOrder\Models\TravelOrderMember;
class TravelOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'request_type',
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
        'country',
        'ticket_type',
        'ticket_arrangement',
        'accommodation_arrangement',
        'created_by',
        'gmo_remark',
        'gmo_evidence',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function members(): HasMany
{
    return $this->hasMany(TravelOrderMember::class, 'travel_order_id');
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

    public function booking(): HasOne
    {
    return $this->hasOne(TravelOrderBooking::class, 'travel_order_id');
    }
    public function approvals(): HasMany
    {
    return $this->hasMany(TravelOrderApproval::class)->orderBy('sequence');
    }
    public function emails(): HasMany
    {
    return $this->hasMany(TravelOrderEmail::class, 'travel_order_id')->latest();
}
}