<?php

namespace Modules\AnnualTicket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AnnualTicketLegCancellation extends Model
{
    protected $primaryKey = 'leg_cancellation_id';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'request_id',
        'leg_id',
        'reason',
        'refund_status',
        'currency',
        'original_amount',
        'refund_amount',
        'non_refundable_amount',
        'confirmation_file',
        'processed_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'original_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'non_refundable_amount' => 'decimal:2',
    ];

    // Supaya frontend langsung dapat URL bukti refund di JSON
    protected $appends = ['confirmation_url'];

    public function getConfirmationUrlAttribute(): ?string
    {
        return $this->confirmation_file ? Storage::disk('public')->url($this->confirmation_file) : null;
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(AnnualTicket::class, 'request_id');
    }

    public function leg(): BelongsTo
    {
        return $this->belongsTo(AnnualTicketLeg::class, 'leg_id');
    }
}
