<?php

namespace Modules\AnnualTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessAnnualTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Fare references dikirim dari FormData sebagai string JSON
    protected function prepareForValidation(): void
    {
        if ($this->filled('fare_references') && is_string($this->input('fare_references'))) {
            $this->merge([
                'fare_references' => json_decode($this->input('fare_references'), true),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:1000'],
            'fare_references' => ['nullable', 'array', 'max:2'],
            'fare_references.*.direction' => ['required_with:fare_references.*.operator_name,fare_references.*.fare_amount', Rule::in(['departure', 'return'])],
            'fare_references.*.operator_name' => ['required_with:fare_references.*.fare_amount', 'string', 'max:150'],
            'fare_references.*.fare_amount' => ['required_with:fare_references.*.operator_name', 'numeric', 'min:1'],
            'fare_references.*.currency' => ['nullable', 'string', 'max:10'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
