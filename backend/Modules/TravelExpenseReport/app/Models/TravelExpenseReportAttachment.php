<?php

namespace Modules\TravelExpenseReport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TravelExpenseReportAttachment extends Model
{
    protected $fillable = [
        'travel_expense_report_id',
        'file_path',
        'original_name',
    ];

    protected $appends = ['file_url'];

    public function getFileUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(TravelExpenseReport::class, 'travel_expense_report_id');
    }
}
