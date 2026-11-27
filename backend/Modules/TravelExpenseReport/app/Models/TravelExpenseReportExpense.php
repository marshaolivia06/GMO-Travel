<?php

declare(strict_types=1);

namespace Modules\TravelExpenseReport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TravelExpenseReportExpense extends Model
{
    // Kategori expense yang dikenali - sinkron dengan kategori di ExpenseEntryDialog
    public const CATEGORIES = [
        'breakfast', 'lunch', 'dinner', 'pocket_money', 'ticket', 'accommodation',
        'laundry', 'telephone', 'office_telephone', 'transport', 'other',
    ];

    // Kategori berbasis struk - attachment wajib saat ada amount
    public const RECEIPT_CATEGORIES = ['ticket', 'accommodation', 'laundry', 'transport', 'other'];

    // Fixed basis - entitlement per hari yang tetap direkam walaupun tanpa aktual
    public const FIXED_CATEGORIES = ['breakfast', 'lunch', 'dinner', 'pocket_money', 'telephone'];

    public const PAYMENT_MYSELF = 'myself';

    public const PAYMENT_PAID_BY_OTHER = 'paid_by_other';

    public const PAYMENT_PAY_FOR_OTHERS = 'pay_for_others';

    public const PAYMENT_MODES = [self::PAYMENT_MYSELF, self::PAYMENT_PAID_BY_OTHER, self::PAYMENT_PAY_FOR_OTHERS];

    protected $fillable = [
        'travel_expense_report_id',
        'day_number',
        'category',
        'item_index',
        'kind',
        'description',
        'currency',
        'actual_amount',
        'eligible_amount',
        'manual_review',
        'payment_mode',
        'paid_by',
        'paid_for',
        'dinner_type',
        'justification',
        'hotel_name',
        'room_type',
        'hotel_standard',
        'attachment_path',
        'attachment_name',
    ];

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? Storage::disk('public')->url($this->attachment_path) : null;
    }

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'item_index' => 'integer',
            'actual_amount' => 'decimal:2',
            'eligible_amount' => 'decimal:2',
            'manual_review' => 'boolean',
            'paid_for' => 'array',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(TravelExpenseReport::class, 'travel_expense_report_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(\Modules\UserManagement\Models\User::class, 'paid_by');
    }
}
