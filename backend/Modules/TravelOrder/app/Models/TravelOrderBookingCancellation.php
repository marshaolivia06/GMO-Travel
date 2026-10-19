<?php

namespace Modules\TravelOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;

class TravelOrderBookingCancellation extends Model
{
    protected $table = 'travel_order_booking_cancellations';

    protected $fillable = [
        'travel_order_id',
        'booking_id',
        'item_type',
        'reason',
        'refund_status',
        'currency',
        'original_amount',
        'refund_amount',
        'non_refundable_amount',
        'confirmation_file',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'non_refundable_amount' => 'decimal:2',
        ];
    }

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class, 'travel_order_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(TravelOrderBooking::class, 'booking_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
