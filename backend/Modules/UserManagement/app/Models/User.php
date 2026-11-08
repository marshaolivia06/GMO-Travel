<?php

declare(strict_types=1);

namespace Modules\UserManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\MasterEmployee;
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

    public function employee(): HasOne
    {
        return $this->hasOne(MasterEmployee::class);
    }

    public function eligibleDestination(): HasOneThrough
    {
        return $this->hasOneThrough(
            MasterDestination::class,
            MasterEmployee::class,
            'user_id',
            'id',
            'id',
            'eligible_destination_id',
        );
    }
}
