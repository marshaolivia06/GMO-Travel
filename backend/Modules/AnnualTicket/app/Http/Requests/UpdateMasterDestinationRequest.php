<?php

namespace Modules\AnnualTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMasterDestinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city_name' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}