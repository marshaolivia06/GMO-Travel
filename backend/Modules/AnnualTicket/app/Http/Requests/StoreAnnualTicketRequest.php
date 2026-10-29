<?php

namespace Modules\AnnualTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnualTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('travelers') && is_string($this->input('travelers'))) {
            $this->merge([
                'travelers' => json_decode($this->input('travelers'), true),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'quota_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'route_condition' => ['required', Rule::in(['Same Route', 'Different Route'])],
            'ticket_arrangement' => ['required', Rule::in(['Direct Payment', 'Booked by Company'])],
            'remarks' => [
                Rule::requiredIf($this->input('route_condition') === 'Different Route'),
                'nullable',
                'string',
                'max:1000',
            ],
            'evidence' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'travelers' => ['required', 'array', 'min:1'],
            'travelers.*.type' => ['required', Rule::in(['employee', 'dependent'])],
            'travelers.*.traveler_id' => [
                'nullable',
                'integer',
                Rule::exists('master_dependents', 'id'),
            ],
            'travelers.*.name' => ['required', 'string', 'max:150'],
            'travelers.*.relationship' => ['nullable', 'string', 'max:50'],
            'travelers.*.departure_date' => ['nullable', 'date', 'required_with:travelers.*.departure_from,travelers.*.departure_to'],
            'travelers.*.departure_from' => ['nullable', 'string', 'max:150', 'required_with:travelers.*.departure_date'],
            'travelers.*.departure_to' => ['nullable', 'string', 'max:150', 'required_with:travelers.*.departure_date'],
            'travelers.*.return_date' => ['nullable', 'date', 'required_with:travelers.*.return_from,travelers.*.return_to'],
            'travelers.*.return_from' => ['nullable', 'string', 'max:150', 'required_with:travelers.*.return_date'],
            'travelers.*.return_to' => ['nullable', 'string', 'max:150', 'required_with:travelers.*.return_date'],
        ];
    }
}
