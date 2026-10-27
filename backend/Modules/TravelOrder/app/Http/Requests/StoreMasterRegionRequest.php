<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:master_regions,code'],
            'name' => ['required', 'string', 'max:255'],
            'coverage' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'in:IDR,USD,SGD,EUR'],
            'travel_area_label' => ['nullable', 'string', 'max:255'],
            'destinations' => ['nullable', 'array'],
            'destinations.*' => ['required', 'string', 'max:255', 'distinct'],
        ];
    }
}