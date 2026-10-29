<?php

namespace Modules\AnnualTicket\Models;

use Modules\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnnualTicket extends Model
{
    protected $fillable = [
        'employee_id',
        'quota_year',
        'route_condition',
        'ticket_arrangement',
        'status',
        'remarks',
        'evidence_path',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function legs(): HasMany
    {
        return $this->hasMany(AnnualTicketLeg::class);
    }
}
