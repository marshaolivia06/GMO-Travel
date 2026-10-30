<?php

namespace Modules\AnnualTicket\Models;

use Modules\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class AnnualTicket extends Model
{
    protected $fillable = [
        'employee_id',
        'quota_year',
        'route_condition',
        'ticket_arrangement',
        'status',
        'document_number',
        'remarks',
        'evidence_path',
    ];

    // Supaya frontend langsung dapat URL lampiran evidence di JSON
    protected $appends = ['evidence_url'];

    public function getEvidenceUrlAttribute(): ?string
    {
        return $this->evidence_path ? Storage::disk('public')->url($this->evidence_path) : null;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function legs(): HasMany
    {
        return $this->hasMany(AnnualTicketLeg::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(AnnualTicketApproval::class)->orderBy('sequence');
    }

    public function fareReferences(): HasMany
    {
        return $this->hasMany(AnnualTicketFareReference::class, 'request_id');
    }

    public function fareReferenceAttachments(): HasMany
    {
        return $this->hasMany(AnnualTicketFareReferenceAttachment::class, 'request_id');
    }
}
