<?php

namespace Modules\TravelOrder\Models;

use Illuminate\Database\Eloquent\Model;

class TravelAdvanceMaster extends Model
{
    protected $table = 'travel_advance_masters';
    protected $primaryKey = 'id_travel_advance_master';

    protected $fillable = [
        'travel_region',
        'grade_min',
        'grade_max',
        'country',
        'currency',
        'pocket_money_limit',
        'meal_allowance_limit',
        'status',
    ];

    protected $casts = [
        'grade_min' => 'integer',
        'grade_max' => 'integer',
        'pocket_money_limit' => 'decimal:2',
        'meal_allowance_limit' => 'decimal:2',
        'status' => 'boolean',
    ];

    protected $appends = ['grade'];

    public function getGradeAttribute()
    {
        if (is_null($this->grade_min) || is_null($this->grade_max)) return null;

        return $this->grade_min == $this->grade_max
            ? (string) $this->grade_min
            : "{$this->grade_min}-{$this->grade_max}";
    }

    public function scopeForGrade($query, int $grade)
    {
        return $query->where('grade_min', '<=', $grade)->where('grade_max', '>=', $grade);
    }
}