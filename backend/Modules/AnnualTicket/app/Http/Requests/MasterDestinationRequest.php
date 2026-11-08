<?php

namespace Modules\AnnualTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterDestinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination_code' => ['required', 'string', 'max:20', 'unique:master_destinations,destination_code'],
            'city_name' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}