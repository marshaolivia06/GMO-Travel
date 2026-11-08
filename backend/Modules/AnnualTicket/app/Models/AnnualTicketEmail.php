<?php

namespace Modules\AnnualTicket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;

class AnnualTicketEmail extends Model
{
    protected $table = 'annual_ticket_emails';

    protected $fillable = [
        'annual_ticket_id',
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

    public function annualTicket(): BelongsTo
    {
        return $this->belongsTo(
            AnnualTicket::class,
            'annual_ticket_id'
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
