<?php

namespace Modules\TravelOrder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddGroupMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.required' => 'Pilih minimal satu anggota.',
            'user_ids.*.exists' => 'Salah satu user yang dipilih tidak ditemukan.',
        ];
    }
}