<?php

namespace Modules\TravelOrder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\TravelOrder\Enums\ActivityCategory;

class UpdateTravelOrderGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'trip_name' => ['required', 'string', 'max:255'],
            'region_id' => ['required', 'integer', Rule::exists('master_regions', 'id')],
            'travel_region' => ['nullable', 'string', 'max:255'],
            'activity_category' => ['required', Rule::enum(ActivityCategory::class)],
            'travel_from' => ['required', 'string', 'max:255'],
            'travel_to' => ['required', 'string', 'max:255'],
            'departure_date' => ['required', 'date'],
            'return_date' => ['required', 'date', 'after_or_equal:departure_date'],
            'purpose' => ['nullable', 'string'],
            'members' => ['required', 'array', 'min:2'],
            'members.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }
}