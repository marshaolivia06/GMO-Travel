<?php

namespace Modules\TravelOrder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTravelOrderGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'trip_name' => ['required', 'string', 'max:255'],
            'travel_region' => ['required', 'string', 'max:255'],
            'travel_from' => ['required', 'string', 'max:255'],
            'travel_to' => ['required', 'string', 'max:255'],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after_or_equal:departure_date'],
            'purpose' => ['nullable', 'string'],
            'members' => ['required', 'array', 'min:1'],
            'members.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }
}