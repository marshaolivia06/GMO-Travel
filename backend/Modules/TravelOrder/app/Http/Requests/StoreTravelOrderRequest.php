<?php

namespace Modules\TravelOrder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\TravelOrder\Models\MasterRegion;

class StoreTravelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $status = $this->input('status');
        $tripType = $this->input('trip_type', 'individual');
        $isSubmitted = $status === 'submitted';
        $isGroupTrip = $tripType === 'group';
        $isAdvanceOpenRequest = $status === 'awaiting_gmo_open_travel_advance';
        $region = $this->filled('region_id') ? MasterRegion::find($this->input('region_id')) : null;
        $allowedCurrencies = $region ? [$region->currency] : [];

        return [
            'status' => ['required', Rule::in(['draft', 'submitted', 'awaiting_gmo_open_travel_advance', 'awaiting_input_travel_advance', 'awaiting_submit_without_travel_advance'])],
            'request_type' => ['required', Rule::in(['business_trip'])],
            'trip_type' => ['required', Rule::in(['individual', 'group'])],
            'travel_order_group_id' => [Rule::requiredIf($isGroupTrip), 'nullable', 'integer', Rule::exists('travel_order_groups', 'id')->where('status', 'open')],
            'members' => ['nullable', 'array'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'travel_from' => ['required_if:status,submitted', 'nullable', 'string', 'max:255'],
            'travel_to' => ['required_if:status,submitted', 'nullable', 'string', 'max:255'],
            'departure_date' => ['required_if:status,submitted', 'nullable', 'date'],
            'departure_time' => ['required_if:status,submitted', 'nullable', 'date_format:H:i'],
            'return_date' => ['required_if:status,submitted', 'nullable', 'date', 'after_or_equal:departure_date'],
            'return_time' => ['required_if:status,submitted', 'nullable', 'date_format:H:i'],
            'purpose' => ['required_if:status,submitted', 'nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'region_id' => [Rule::requiredIf($isSubmitted || $isAdvanceOpenRequest), 'nullable', 'integer', 'exists:master_regions,id'],
            'travel_region' => ['nullable', Rule::in(['Domestic', 'Singapore', 'Overseas'])],
            'country' => ['required_if:status,submitted', 'nullable', 'string', 'max:255'],
            'ticket_type' => ['required_if:status,submitted', 'nullable', Rule::in(['Ferry', 'Airplane'])],
            'ticket_arrangement' => ['required_if:status,submitted', 'nullable', Rule::in(['Direct Payment', 'Booked by Company'])],
            'accommodation_arrangement' => ['required_if:status,submitted', 'nullable', Rule::in(['Direct Payment', 'Booked by Company', 'Not Required'])],
            'meal_allowance' => ['nullable', 'numeric', 'min:0'],
            'pocket_money' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', Rule::in($allowedCurrencies)],
        ];
    }
}