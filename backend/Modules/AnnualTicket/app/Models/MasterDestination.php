<?php

namespace Modules\AnnualTicket\Models;

use Illuminate\Database\Eloquent\Model;

class MasterDestination extends Model
{
    protected $table = 'master_destinations';

    protected $fillable = [
        'destination_code',
        'city_name',
        'country',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}