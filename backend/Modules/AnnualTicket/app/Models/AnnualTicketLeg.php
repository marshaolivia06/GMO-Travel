<?php

namespace Modules\AnnualTicket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnualTicketLeg extends Model
{
    protected $fillable = [
        'annual_ticket_id',
        'traveler_type',
        'traveler_id',
        'name',
        'relationship',
        'direction',
        'travel_date',
        'reschedule_date',
        'travel_from',
        'travel_to',
    ];

    protected $casts = [
        'travel_date' => 'date',
        'reschedule_date' => 'date',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(AnnualTicket::class, 'annual_ticket_id');
    }
}
