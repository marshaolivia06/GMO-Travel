<?php

namespace Modules\AuthorityMatrix\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\Section;
use Modules\UserManagement\Models\Role;

class AuthorityMatrixStep extends Model
{
    protected $fillable = [
        'authority_matrix_id',
        'step',
        'role_id',
        'label',
        'role_level',
        'is_specific_section',
        'section_id',
        'is_specific_department',
        'department_id',
    ];

    protected $casts = [
        'is_specific_section' => 'boolean',
        'is_specific_department' => 'boolean',
    ];

    public function authorityMatrix(): BelongsTo
    {
        return $this->belongsTo(AuthorityMatrix::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}