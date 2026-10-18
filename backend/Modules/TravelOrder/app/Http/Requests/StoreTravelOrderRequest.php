<?php

namespace Modules\TravelOrder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\MasterManagement\Models\Section;

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

        $activeSectionHeadIds = Section::query()
            ->where('status', 1)
            ->whereNotNull('section_head_id')
            ->whereHas('sectionHead', fn ($query) => $query->where('status', 1))
            ->pluck('section_head_id')
            ->unique()
            ->values()
            ->all();

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

            'members' => [
                Rule::requiredIf($isSubmitted && $isGroupTrip),
                'nullable',
                'array',
                'min:' . ($isSubmitted && $isGroupTrip ? 1 : 0),
            ],

            'members.*' => [
                'required',
                'integer',
                'distinct',
                Rule::in($activeSectionHeadIds),
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
