<?php

namespace Modules\AnnualTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterDependentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
    return ['employee_id' => ['required', 'exists:users,id', 'unique:master_dependents,employee_id'], 'dependents' => ['required', 'array', 'min:1'], 'dependents.*.name' => ['required', 'string', 'max:150'], 'dependents.*.relationship' => ['required', 'in:Spouse,Child'], 'dependents.*.dob' => ['required', 'date', 'before:today']];
    }
    
}