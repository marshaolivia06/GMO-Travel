<?php

namespace Modules\TravelOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;
use Spatie\Permission\Models\Role;

class TravelOrderApproval extends Model
{
    protected $fillable = [
        'travel_order_id',
        'sequence',
        'role_id',
        'user_id',
        'status',
        'note',
        'acted_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    // Supaya frontend langsung dapat nama approver dan nama role di JSON
    protected $appends = ['name', 'role_name'];

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    public function getRoleNameAttribute(): ?string
    {
        return $this->role?->name;
    }
}