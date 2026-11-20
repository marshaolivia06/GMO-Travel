<?php

namespace Modules\TravelOrder\Models; 

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\TravelOrder\Enums\ActivityCategory;
use Modules\UserManagement\Models\User;
use Modules\TravelOrder\Models\MasterRegion;

class TravelOrderGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'trip_name',
        'travel_region',
        'region_id',
        'activity_category',
        'travel_from',
        'travel_to',
        'departure_date',
        'return_date',
        'purpose',
        'status',
        'created_by',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'activity_category' => ActivityCategory::class,
    ];

    protected $appends = ['activity_category_label'];

    public function getActivityCategoryLabelAttribute(): ?string
    {
        return $this->activity_category?->label();
    }

    public function creator(): BelongsTo
    {
    return $this->belongsTo(User::class, 'created_by');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(MasterRegion::class, 'region_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TravelOrderGroupMember::class);
    }

    public function travelOrders(): HasMany
    {
        return $this->hasMany(TravelOrder::class);
    }
}