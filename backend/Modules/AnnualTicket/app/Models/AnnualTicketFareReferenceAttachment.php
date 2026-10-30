<?php

namespace Modules\AnnualTicket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AnnualTicketFareReferenceAttachment extends Model
{
    protected $primaryKey = 'fare_reference_attachment_id';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'request_id',
        'file_path',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Supaya frontend langsung dapat URL lampiran di JSON
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
