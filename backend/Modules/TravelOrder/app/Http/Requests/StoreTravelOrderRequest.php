<?php

namespace Modules\TravelOrder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTravelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $status = $this->input('status');
        $travelRegion = $this->input('travel_region');
        $tripType = $this->input('trip_type', 'individual');
        $isSubmitted = $status === 'submitted';
        $isGroupTrip = $tripType === 'group';

        $isAdvanceRequired = in_array(
            $travelRegion,
            ['Singapore', 'Overseas'],
            true
        );

        $allowedCurrencies = match ($travelRegion) {
            'Domestic' => ['IDR'],
            'Singapore' => ['SGD'],
            'Overseas' => ['USD', 'EUR', 'MYR', 'JPY'],
            default => [],
        };

        return [
            'status' => [
                'required',
                Rule::in(['draft', 'submitted']),
            ],

            'request_type' => [
                'required',
                Rule::in(['business_trip', 'annual_leave']),
            ],

            'trip_type' => [
                'required',
                Rule::in(['individual', 'group']),
            ],

            // Group harus masih open. Pengecekan anggota dan satu group satu
            // Travel Order dilakukan di TravelOrderService::assertGroupMember.
            'travel_order_group_id' => [
                Rule::requiredIf($isGroupTrip),
                'nullable',
                'integer',
                Rule::exists('travel_order_groups', 'id')->where('status', 'open'),
            ],

            // Anggota diambil dari group, jadi tidak lagi dikirim dari form.
            'members' => [
                'nullable',
                'array',
            ],

            'department_id' => [
                'nullable',
                'integer',
                'exists:departments,id',
            ],

            'travel_from' => [
                'required_if:status,submitted',
                'nullable',
                'string',
                'max:255',
            ],

            'travel_to' => [
                'required_if:status,submitted',
                'nullable',
                'string',
                'max:255',
            ],

            'departure_date' => [
                'required_if:status,submitted',
                'nullable',
                'date',
            ],

            'departure_time' => [
                'required_if:status,submitted',
                'nullable',
                'date_format:H:i',
            ],

            'return_date' => [
                'required_if:status,submitted',
                'nullable',
                'date',
                'after_or_equal:departure_date',
            ],

            'return_time' => [
                'required_if:status,submitted',
                'nullable',
                'date_format:H:i',
            ],

            'purpose' => [
                'required_if:status,submitted',
                'nullable',
                'string',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

            'travel_region' => [
                'required_if:status,submitted',
                'nullable',
                Rule::in(['Domestic', 'Singapore', 'Overseas']),
            ],

            'country' => [
                'required_if:status,submitted',
                'nullable',
                'string',
                'max:255',
            ],

            'ticket_type' => [
                'required_if:status,submitted',
                'nullable',
                Rule::in(['Ferry', 'Airplane']),
            ],

            'ticket_arrangement' => [
                'required_if:status,submitted',
                'nullable',
                Rule::in(['Direct Payment', 'Booked by Company']),
            ],

            'accommodation_arrangement' => [
                'required_if:status,submitted',
                'nullable',
                Rule::in([
                    'Direct Payment',
                    'Booked by Company',
                    'Not Required',
                ]),
            ],

            'meal_allowance' => [
                Rule::requiredIf($isSubmitted && $isAdvanceRequired),
                'nullable',
                'numeric',
                'min:0',
            ],

            'pocket_money' => [
                Rule::requiredIf($isSubmitted && $isAdvanceRequired),
                'nullable',
                'numeric',
                'min:0',
            ],

            'currency' => [
                Rule::requiredIf($isSubmitted && $isAdvanceRequired),
                'nullable',
                Rule::in($allowedCurrencies),
            ],
        ];
    }
}