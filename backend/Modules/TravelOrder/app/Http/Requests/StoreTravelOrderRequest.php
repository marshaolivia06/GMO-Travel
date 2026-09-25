<?php

namespace Modules\TravelOrder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTravelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isOverseas = in_array($this->input('travel_region'), ['Singapore', 'Non Singapore']);

        $allowedCurrencies = $this->input('travel_region') === 'Singapore'
            ? ['SGD']
            : ['USD', 'EUR', 'MYR', 'JPY'];

        return [
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],

            'travel_from' => ['required', 'string', 'max:255'],
            'travel_to' => ['required', 'string', 'max:255'],
            'departure_date' => ['required', 'date'],
            'departure_time' => ['required', 'date_format:H:i'],
            'return_date' => ['required', 'date', 'after_or_equal:departure_date'],
            'return_time' => ['required', 'date_format:H:i'],
            'purpose' => ['required', 'string'],
            'remarks' => ['nullable', 'string'],

            'travel_region' => ['required', Rule::in(['Indonesia', 'Singapore', 'Non Singapore'])],

            'ferry_ticket_type' => ['required', Rule::in(['Ferry', 'Airplane'])],
            'ferry_arrangement' => ['required', Rule::in(['Direct Payment', 'Booked by Company'])],
            'accommodation_arrangement' => ['required', Rule::in(['Direct Payment', 'Booked by Company', 'Not Required'])],

            'meal_allowance' => [Rule::requiredIf($isOverseas), 'nullable', 'numeric', 'min:0'],
            'meal_currency' => [Rule::requiredIf($isOverseas), 'nullable', Rule::in($allowedCurrencies)],
            'pocket_money' => [Rule::requiredIf($isOverseas), 'nullable', 'numeric', 'min:0'],
            'pocket_currency' => [Rule::requiredIf($isOverseas), 'nullable', Rule::in($allowedCurrencies)],
        ];
    }
}