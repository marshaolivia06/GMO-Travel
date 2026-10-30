<?php

namespace Modules\AnnualTicket\Http\Requests;

class ResubmitAnnualTicketRequest extends StoreAnnualTicketRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }
}
