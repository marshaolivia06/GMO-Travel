<?php

declare(strict_types=1);

namespace Modules\UserManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\MasterManagement\Models\Department;
use Spatie\Permission\Traits\HasRoles;
use Modules\AnnualTicket\Models\MasterDestination;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'grade',
        'eligible_destination_id',
    ];    

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => 'boolean',
        'grade' => 'integer',
    ];
}

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function getDefaultGuardName(): string
    {
        return 'web';
    }

    public function eligibleDestination(): BelongsTo
    {
        return $this->belongsTo(MasterDestination::class, 'eligible_destination_id');
    }
}
