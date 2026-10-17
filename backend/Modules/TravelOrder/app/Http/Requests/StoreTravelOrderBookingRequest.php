<?php

namespace Modules\TravelOrder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTravelOrderBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'departure_date' => ['nullable', 'date'],
            'departure_time' => ['nullable', 'date_format:H:i'],
            'return_date' => ['nullable', 'date'],
            'return_time' => ['nullable', 'date_format:H:i'],

            'ticket_currency' => ['nullable', 'string', 'max:10'],
            'ticket_amount' => ['nullable', 'numeric', 'min:0'],

            'accommodation_currency' => ['nullable', 'string', 'max:10'],
            'accommodation_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
