<?php

namespace Modules\AnnualTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMasterDependentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['dependents' => ['required', 'array', 'min:1'], 'dependents.*.id' => ['nullable', 'integer'], 'dependents.*.name' => ['required', 'string', 'max:150'], 'dependents.*.relationship' => ['required', 'in:Spouse,Child'], 'dependents.*.dob' => ['required', 'date', 'before:today']];
    }
}