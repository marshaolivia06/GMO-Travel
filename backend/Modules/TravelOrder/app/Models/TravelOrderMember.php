<?php

namespace Modules\TravelOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;
use Modules\MasterManagement\Models\Department;

class TravelOrderMember extends Model
{
    protected $fillable = [
        'travel_order_id',
        'user_id',
        'department_id',
    ];

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class, 'travel_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}