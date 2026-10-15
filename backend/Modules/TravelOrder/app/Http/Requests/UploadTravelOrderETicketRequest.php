<?php

namespace Modules\TravelOrder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UploadTravelOrderETicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
    return [
        'e_ticket' => [
            'nullable',
            'required_without:hotel_reservation',
            File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(10 * 1024),
        ],
        'hotel_reservation' => [
            'nullable',
            'required_without:e_ticket',
            File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(10 * 1024),
        ],
    ];
}

}