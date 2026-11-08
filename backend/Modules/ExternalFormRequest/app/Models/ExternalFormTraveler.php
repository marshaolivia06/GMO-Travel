<?php

namespace Modules\ExternalFormRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalFormTraveler extends Model
{
    protected $fillable = [
        'external_form_request_id',
        'name',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ExternalFormRequest::class, 'external_form_request_id');
    }
}
