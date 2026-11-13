<?php

namespace Modules\TravelExpenseReport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Modules\AnnualTicket\Models\AnnualTicket;
use Modules\UserManagement\Models\User;

class TravelExpenseReport extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const SOURCE_ANNUAL_TICKET = 'annual_ticket';

    public const SOURCE_TRAVEL_ORDER = 'travel_order';

    public const CLAIM_TICKET_REIMBURSEMENT = 'ticket_reimbursement';

    public const CLAIM_EXCESS_SETTLEMENT = 'excess_settlement';

    protected $fillable = [
        'employee_id',
        'source_type',
        'annual_ticket_id',
        'claim_type',
        'status',
        'document_number',
        'currency',
        'total_fare_reference',
        'total_actual_amount',
        'settlement_amount',
        'company_booking_amount',
        'employee_excess',
        'proof_of_payment_path',
        'submitted_at',
    ];

    // Supaya frontend langsung dapat URL file proof of payment di JSON
    protected $appends = ['proof_of_payment_url'];

    public function getProofOfPaymentUrlAttribute(): ?string
    {
        return $this->proof_of_payment_path ? Storage::disk('public')->url($this->proof_of_payment_path) : null;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function annualTicket(): BelongsTo
    {
        return $this->belongsTo(AnnualTicket::class, 'annual_ticket_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TravelExpenseReportAttachment::class);
    }
}
