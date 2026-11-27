<?php

declare(strict_types=1);

namespace Modules\TravelExpenseReport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelExpenseReportDay extends Model
{
    protected $fillable = [
        'travel_expense_report_id',
        'day_number',
        'date',
        'type',
        'effective_time',
        'different_countries',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'day_number' => 'integer',
            'different_countries' => 'boolean',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(TravelExpenseReport::class, 'travel_expense_report_id');
    }
}
