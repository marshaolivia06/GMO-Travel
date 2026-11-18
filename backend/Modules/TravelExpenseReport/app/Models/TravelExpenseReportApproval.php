<?php

namespace Modules\TravelExpenseReport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;
use Spatie\Permission\Models\Role;

class TravelExpenseReportApproval extends Model
{
    protected $table = 'travel_expense_report_approvals';

    protected $fillable = [
        'travel_expense_report_id',
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
    protected $appends = ['name', 'role_name'];

    public function report(): BelongsTo
    {
        return $this->belongsTo(TravelExpenseReport::class, 'travel_expense_report_id');
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
