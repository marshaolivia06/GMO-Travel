<?php

namespace Modules\MasterManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\UserManagement\Models\User;

class Section extends Model
{
    protected $fillable = [
        'name',
        'section_head_id',
        'supervisor_id',
        'department_id',
        'area',
        'status',
    ];

    public function sectionHead(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'section_head_id'
        );
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'supervisor_id'
        );
    }

    public function staff(): HasMany
    {
        return $this->hasMany(
            SectionStaff::class,
            'section_id'
        );
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class,
            'department_id'
        );
    }
}
