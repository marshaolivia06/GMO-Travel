<?php

namespace Modules\AnnualTicket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AnnualTicketBookingDocument extends Model
{
    protected $primaryKey = 'booking_document_id';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'request_id',
        'file_path',
        'original_name',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Supaya frontend langsung dapat URL dokumen di JSON
    protected $appends = ['file_url'];

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(AnnualTicket::class, 'request_id');
    }
}
