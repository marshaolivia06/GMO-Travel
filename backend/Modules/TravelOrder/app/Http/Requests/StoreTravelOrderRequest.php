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
        $travelRegion = $this->input('travel_region');

        $isAdvanceRequired = in_array(
            $travelRegion,
            ['Indonesia', 'Singapore', 'Non Singapore'],
            true
        );

        $allowedCurrencies = match ($travelRegion) {
            'Indonesia' => ['IDR'],
            'Singapore' => ['SGD'],
            'Non Singapore' => ['USD', 'EUR', 'MYR', 'JPY'],
            default => [],
        };

        return [
            'department_id' => [
                'nullable',
                'integer',
                'exists:departments,id',
            ],

            'travel_from' => [
                'required',
                'string',
                'max:255',
            ],

            'travel_to' => [
                'required',
                'string',
                'max:255',
            ],

            'departure_date' => [
                'required',
                'date',
            ],

            'departure_time' => [
                'required',
                'date_format:H:i',
            ],

            'return_date' => [
                'required',
                'date',
                'after_or_equal:departure_date',
            ],

            'return_time' => [
                'required',
                'date_format:H:i',
            ],

            'purpose' => [
                'required',
                'string',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

            'travel_region' => [
                'required',
                Rule::in([
                    'Indonesia',
                    'Singapore',
                    'Non Singapore',
                ]),
            ],

            'ferry_ticket_type' => [
                'required',
                Rule::in([
                    'Ferry',
                    'Airplane',
                ]),
            ],

            'ferry_arrangement' => [
                'required',
                Rule::in([
                    'Direct Payment',
                    'Booked by Company',
                ]),
            ],

            'accommodation_arrangement' => [
                'required',
                Rule::in([
                    'Direct Payment',
                    'Booked by Company',
                    'Not Required',
                ]),
            ],

            'meal_allowance' => [
                Rule::requiredIf($isAdvanceRequired),
                'nullable',
                'numeric',
                'min:0',
            ],

            'pocket_money' => [
                Rule::requiredIf($isAdvanceRequired),
                'nullable',
                'numeric',
                'min:0',
            ],

            'currency' => [
                Rule::requiredIf($isAdvanceRequired),
                'nullable',
                Rule::in($allowedCurrencies),
            ],
        ];
    }
}
