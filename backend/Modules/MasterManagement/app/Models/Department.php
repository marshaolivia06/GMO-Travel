<?php

namespace Modules\MasterManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;

class Department extends Model
{
    protected $table = 'departments';

    protected $fillable = [
        'name',
        'category_id',
        'dept_head_id',
        'dept_admin_id',
        'division_head_id',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function deptHead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dept_head_id');
    }

    public function deptAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dept_admin_id');
    }

    public function divisionHead(): BelongsTo
    {
        return $this->belongsTo(MasterDivision::class, 'division_head_id');
    }
}
