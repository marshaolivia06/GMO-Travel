<?php

namespace Modules\MasterManagement\Models;

use Illuminate\Database\Eloquent\Model;

class MasterDivision extends Model
{
    protected $table = 'master_division';

    protected $fillable = [
        'division_name',
    ];
}
