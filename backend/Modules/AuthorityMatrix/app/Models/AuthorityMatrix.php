<?php

namespace Modules\AuthorityMatrix\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuthorityMatrix extends Model
{
    protected $fillable = [
        'document_type',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(AuthorityMatrixStep::class);
    }
}