<?php

namespace Modules\AnnualTicket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnualTicketFareReference extends Model
{
    protected $primaryKey = 'fare_reference_id';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'request_id',
        'direction',
        'operator_name',
        'fare_amount',
        'currency',
        'created_by',
    ];

    protected $casts = [
        'fare_amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(AnnualTicket::class, 'request_id');
    }
}
