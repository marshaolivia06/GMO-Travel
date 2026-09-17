<?php

namespace Modules\MasterManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\MasterManagement\Models\Department;
use Modules\UserManagement\Models\User;

class Section extends Model
{
    protected $fillable = [
        'name',
        'section_head_id',
        'department_id',
        'status',
    ];

    public function sectionHead(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'section_head_id'
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
