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

    public const STATUS_RELEASED = 'released';

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
        'operator',
        'flight_vessel_no',
        'departure_atd',
        'departure_ata',
        'return_operator',
        'return_flight_vessel_no',
        'return_atd',
        'return_ata',
        'hotel',
        'room_type',
        'remark',
        'departure_booking_code',
        'return_booking_code',
        'reservation_confirmation_no',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date:Y-m-d',
            'return_date' => 'date:Y-m-d',
            'check_in_date' => 'date:Y-m-d',
            'check_out_date' => 'date:Y-m-d',
            'departure_atd' => 'datetime',
            'departure_ata' => 'datetime',
            'return_atd' => 'datetime',
            'return_ata' => 'datetime',
        ];
    }

    // Supaya frontend langsung dapat URL lampiran hasil release di JSON (kolom Attachment)
    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute(): ?string
    {
        $path = $this->emails->firstWhere('attachment_path')?->attachment_path;

        return $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
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

    public function emails(): HasMany
    {
        return $this->hasMany(ExternalFormEmail::class)->latest();
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
