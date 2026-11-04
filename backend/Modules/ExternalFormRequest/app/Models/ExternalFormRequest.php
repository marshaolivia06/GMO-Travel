<?php

namespace Modules\ExternalFormRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\UserManagement\Models\User;

class ExternalFormRequest extends Model
{
    public const TYPE_AIRPLANE_FERRY = 'airplane_ferry';

    public const TYPE_HOTEL_RESERVATION = 'hotel_reservation';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_AWAITING_DEPT_HEAD = 'awaiting_approval_dept_head';

    public const STATUS_AWAITING_REVIEW_GMO = 'awaiting_review_gmo';

    public const STATUS_AWAITING_DIV_HEAD = 'awaiting_approval_div_head';

    public const STATUS_BOOKING_PAYMENT = 'booking_payment_in_progress';

    protected $fillable = [
        'employee_id',
        'type',
        'status',
        'document_number',
        'transport_mode',
        'vendor_customer',
        'purpose',
        'trip_type',
        'travel_from',
        'departure_date',
        'travel_to',
        'return_date',
        'check_in_date',
        'check_out_date',
        'hotel',
        'room_type',
        'remark',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date:Y-m-d',
            'return_date' => 'date:Y-m-d',
            'check_in_date' => 'date:Y-m-d',
            'check_out_date' => 'date:Y-m-d',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function travelers(): HasMany
    {
        return $this->hasMany(ExternalFormTraveler::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ExternalFormApproval::class)->orderBy('step');
    }

    // Format nomor dokumen: EF-<Ym>-<0001>, urut per bulan
    public static function generateDocumentNumber(): string
    {
        $prefix = 'EF-'.now()->format('Ym').'-';
        $last = static::query()->where('document_number', 'like', $prefix.'%')->orderByDesc('document_number')->value('document_number');
        $next = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
