<?php

namespace Modules\ExternalFormRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;
use Spatie\Permission\Models\Role;

class ExternalFormApproval extends Model
{
    protected $fillable = [
        'external_form_request_id',
        'round',
        'step',
        'role_id',
        'label',
        'action_type',
        'user_id',
        'status',
        'acted_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    // Supaya frontend & PDF langsung dapat nama actor dan nama role
    protected $appends = ['name', 'role_name'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ExternalFormRequest::class, 'external_form_request_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    public function getRoleNameAttribute(): ?string
    {
        return $this->role?->name;
    }
}
