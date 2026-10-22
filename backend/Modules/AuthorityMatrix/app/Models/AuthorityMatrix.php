<?php

namespace Modules\AuthorityMatrix\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\UserManagement\Models\Role;

class AuthorityMatrix extends Model
{
    protected $fillable = [
        'document_type',
        'requester_role_id',
        'travel_region',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(AuthorityMatrixStep::class);
    }

    public function requesterRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'requester_role_id');
    }
}