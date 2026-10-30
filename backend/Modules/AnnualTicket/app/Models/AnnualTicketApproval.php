<?php

namespace Modules\AnnualTicket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class AnnualTicketApproval extends Model
{
    protected $fillable = [
        'annual_ticket_id',
        'sequence',
        'authority_matrix_step_id',
        'action_type',
        'role_id',
        'user_id',
        'status',
        'note',
        'acted_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    // Supaya frontend langsung dapat nama actor dan nama role di JSON
    protected $appends = ['name', 'role_name', 'action_type'];

    public function annualTicket(): BelongsTo
    {
        return $this->belongsTo(AnnualTicket::class);
    }

    public function matrixStep(): BelongsTo
    {
        return $this->belongsTo(\Modules\AuthorityMatrix\Models\AuthorityMatrixStep::class, 'authority_matrix_step_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\UserManagement\Models\User::class);
    }

    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    public function getRoleNameAttribute(): ?string
    {
        return $this->role?->name;
    }

    public function getActionTypeAttribute(): ?string
    {
        return $this->attributes['action_type'] ?? $this->matrixStep?->action_type;
    }
}
