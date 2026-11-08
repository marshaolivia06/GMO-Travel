<?php

declare(strict_types=1);

namespace Modules\MasterManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AnnualTicket\Models\MasterDestination;

class MasterEmployee extends Model
{
    protected $fillable = [
        'user_id',
        'grade',
        'eligible_destination_id',
        'date_of_join',
        'office_location',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            'date_of_join' => 'date:Y-m-d',
        ];
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
