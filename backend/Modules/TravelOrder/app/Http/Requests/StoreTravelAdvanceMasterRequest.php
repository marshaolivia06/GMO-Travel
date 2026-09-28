<?php

namespace Modules\TravelOrder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTravelAdvanceMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
{
    return [
        'travel_region' => [
            'required',
            'string',
            'max:255',
        ],

        'grade_min' => [
            'nullable',
            'integer',
            'min:0',
        ],

        'grade_max' => [
            'nullable',
            'integer',
            'min:0',
        ],

        'country' => [
            'nullable',
            'string',
            'max:255',
        ],

        'currency' => [
            'required',
            'string',
            'size:3',
            'uppercase',
        ],

        'pocket_money_limit' => [
            'required',
            'numeric',
            'min:0',
        ],

        'meal_allowance_limit' => [
            'required',
            'numeric',
            'min:0',
        ],
    ];
}
}