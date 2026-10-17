<?php

namespace Modules\TravelOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;

class TravelOrderEmail extends Model
{
    protected $table = 'travel_order_emails';

    protected $fillable = [
        'travel_order_id',
        'sent_by',
        'to',
        'cc',
        'subject',
        'message',
        'status',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(
            TravelOrder::class,
            'travel_order_id'
        );
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'sent_by'
        );
    }
}
