<?php

namespace Modules\AuthorityMatrix\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuthorityMatrixRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'max:255'],
            'status' => ['boolean'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.step' => ['required', 'integer', 'min:1'],
            'steps.*.role_id' => ['required', 'exists:roles,id'],
            'steps.*.label' => ['required', 'string', 'max:255'],
            'steps.*.role_level' => ['nullable', 'integer', 'min:1'],
            'steps.*.is_specific_section' => ['boolean'],
            'steps.*.section_id' => ['nullable', 'exists:sections,id'],
            'steps.*.is_specific_department' => ['boolean'],
            'steps.*.department_id' => ['nullable', 'exists:departments,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}