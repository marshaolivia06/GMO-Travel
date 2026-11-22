<?php

declare(strict_types=1);

namespace Modules\MasterManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AnnualTicket\Models\MasterDestination;
use Modules\UserManagement\Models\User as AuthUser;

class MasterEmployee extends Model
{
    // Permission dikelola sistem: diberikan/dicabut otomatis mengikuti annual_ticket_eligible
    public const ANNUAL_TICKET_PERMISSION = 'annual-ticket.eligible';

    // Module terpisah agar tidak ikut tersentuh saat aksi module 'annual-ticket' diubah di Permission Management
    public const ANNUAL_TICKET_PERMISSION_MODULE = 'annual-ticket-eligibility';

    protected $fillable = [
        'user_id',
        'grade',
        'eligible_destination_id',
        'date_of_join',
        'office_location',
        'bank_name',
        'bank_account_number',
        'annual_ticket_eligible',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            'date_of_join' => 'date:Y-m-d',
            'annual_ticket_eligible' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (MasterEmployee $employee) => $employee->syncAnnualTicketPermission());
        static::deleted(fn (MasterEmployee $employee) => $employee->syncAnnualTicketPermission(revoke: true));
    }

    public function syncAnnualTicketPermission(bool $revoke = false): void
    {
        $user = AuthUser::find($this->user_id);

        if (! $user) {
            return;
        }

        if (! $revoke && $this->annual_ticket_eligible) {
            $user->givePermissionTo(self::ANNUAL_TICKET_PERMISSION);
        } elseif ($user->hasDirectPermission(self::ANNUAL_TICKET_PERMISSION)) {
            $user->revokePermissionTo(self::ANNUAL_TICKET_PERMISSION);
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function eligibleDestination(): BelongsTo
    {
        return $this->belongsTo(MasterDestination::class, 'eligible_destination_id');
    }
}
