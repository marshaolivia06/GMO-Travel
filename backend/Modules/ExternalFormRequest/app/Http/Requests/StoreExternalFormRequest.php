<?php

namespace Modules\ExternalFormRequest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ExternalFormRequest\Models\ExternalFormRequest;

class StoreExternalFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isSubmitted = $this->input('action') === 'submit';
        $isTransport = $this->input('type') === ExternalFormRequest::TYPE_AIRPLANE_FERRY;
        $isHotel = $this->input('type') === ExternalFormRequest::TYPE_HOTEL_RESERVATION;

        return [
            'type' => ['required', Rule::in([ExternalFormRequest::TYPE_AIRPLANE_FERRY, ExternalFormRequest::TYPE_HOTEL_RESERVATION])],
            'action' => ['required', Rule::in(['draft', 'submit'])],
            'transport_mode' => [Rule::requiredIf($isSubmitted && $isTransport), 'nullable', Rule::in(['Airplane', 'Ferry'])],
            'vendor_customer' => [Rule::requiredIf($isSubmitted), 'nullable', 'string', 'max:150'],
            'purpose' => [Rule::requiredIf($isSubmitted), 'nullable', 'string', 'max:1000'],
            'trip_type' => [Rule::requiredIf($isSubmitted && $isTransport), 'nullable', Rule::in(['one_way', 'round_trip'])],
            'travel_from' => [Rule::requiredIf($isSubmitted && $isTransport), 'nullable', 'string', 'max:150'],
            'departure_date' => [Rule::requiredIf($isSubmitted && $isTransport), 'nullable', 'date'],
            'travel_to' => [Rule::requiredIf($isSubmitted && $isTransport), 'nullable', 'string', 'max:150'],
            'return_date' => [
                'nullable',
                'date',
                Rule::requiredIf($isSubmitted && $isTransport && $this->input('trip_type') === 'round_trip'),
                'after_or_equal:departure_date',
            ],
            'check_in_date' => [Rule::requiredIf($isSubmitted && $isHotel), 'nullable', 'date'],
            'check_out_date' => [
                'nullable',
                'date',
                Rule::requiredIf($isSubmitted && $isHotel),
                'after_or_equal:check_in_date',
            ],
            'remark' => ['nullable', 'string', 'max:1000'],
            'travelers' => [Rule::requiredIf($isSubmitted), 'nullable', 'array', 'min:1'],
            'travelers.*.name' => [Rule::requiredIf($isSubmitted), 'nullable', 'string', 'max:150'],
        ];
    }
}
