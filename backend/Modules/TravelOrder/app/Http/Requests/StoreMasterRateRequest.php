<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'region_id' => ['required', 'integer', 'exists:master_regions,id'],
            'grade_min' => ['required', 'integer', 'min:1', 'max:99'],
            'grade_max' => ['required', 'integer', 'min:1', 'max:99', 'gte:grade_min'],
            'hotel_star' => ['required', 'integer', 'between:1,5'],
            'hotel_rate' => ['required', 'numeric', 'min:0'],
            'hotel_rate_currency' => ['required', 'string', 'in:IDR,USD,SGD,EUR'],
            'meal_basis' => ['required', 'string', 'in:limit,at_cost'],
            'meal_limit_per_meal' => ['nullable', 'required_if:meal_basis,limit', 'numeric', 'min:0'],
            'daily_allowance' => ['nullable', 'numeric', 'min:0'],
            'daily_allowance_max_days' => ['nullable', 'required_with:daily_allowance', 'integer', 'min:1', 'max:255'],
            'flight_class' => ['required', 'string', 'in:economy,economy_business'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}