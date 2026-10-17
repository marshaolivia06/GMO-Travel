<?php

namespace Modules\TravelOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelOrderBooking extends Model
{
    protected $table = 'travel_order_bookings';

    protected $fillable = [
        'travel_order_id',
        'departure_date',
        'departure_time',
        'return_date',
        'return_time',
        'ticket_currency',
        'ticket_amount',
        'e_ticket_path',
        'accommodation_currency',
        'accommodation_amount',
        'hotel_reservation_path',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'return_date' => 'date',
            'ticket_amount' => 'decimal:2',
            'accommodation_amount' => 'decimal:2',
        ];
    }

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class, 'travel_order_id');
    }
}
