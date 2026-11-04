<?php

namespace Modules\AnnualTicket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AnnualTicketBooking extends Model
{
    protected $primaryKey = 'annual_ticket_booking_id';

    protected $fillable = [
        'request_id',
        'amount_currency',
        'amount',
        'e_ticket_path',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Supaya frontend langsung dapat URL E-Ticket di JSON
    protected $appends = ['e_ticket_url'];

    public function getETicketUrlAttribute(): ?string
    {
        return $this->e_ticket_path ? Storage::disk('public')->url($this->e_ticket_path) : null;
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(AnnualTicket::class, 'request_id');
    }
}
