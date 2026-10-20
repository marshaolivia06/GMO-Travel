<?php

namespace Modules\TravelOrder\Models; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;

class TravelOrderGroupMember extends Model
{
    protected $fillable = [
        'travel_order_group_id',
        'user_id',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(TravelOrderGroup::class, 'travel_order_group_id');
    }

    public function user(): BelongsTo
    {
    return $this->belongsTo(User::class);
    }
}