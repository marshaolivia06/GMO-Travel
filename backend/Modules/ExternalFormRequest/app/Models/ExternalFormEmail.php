<?php

namespace Modules\ExternalFormRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;

class ExternalFormEmail extends Model
{
    protected $fillable = [
        'external_form_request_id',
        'sent_by',
        'to',
        'cc',
        'subject',
        'message',
        'attachment_path',
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

    public function form(): BelongsTo
    {
        return $this->belongsTo(ExternalFormRequest::class, 'external_form_request_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
