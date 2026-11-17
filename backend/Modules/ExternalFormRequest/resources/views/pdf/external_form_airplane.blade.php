@php
    $val = fn ($v) => ($v === null || $v === '') ? '-' : $v;
    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d-M-Y') : '-';
    $dateTime = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y H:i') : '-';
    $steps = $approvals->where('step', '>', 0);
    $documentNo = $steps->isNotEmpty() && $steps->every(fn ($step) => in_array($step->status, ['approved', 'reviewed'])) ? $form->document_number : null;

    $isRoundTrip = $form->trip_type !== 'one_way';
    $transportMode = $form->transport_mode ?: 'Airplane';
    $operatorLabel = $transportMode === 'Ferry' ? 'Ferry Operator' : 'Airline Operator';
    $flightLabel = $transportMode === 'Ferry' ? 'Vessel No' : 'Flight No';

    $trips = collect([[
        'title' => 'Departure Trip',
        'route' => $form->travel_from . ' - ' . $form->travel_to,
        'date' => $form->departure_date,
        'operator' => $form->operator,
        'flight' => $form->flight_vessel_no,
        'booking' => $form->departure_booking_code,
        'atd' => $form->departure_atd,
        'ata' => $form->departure_ata,
    ]]);

    if ($isRoundTrip) {
        $trips->push([
            'title' => 'Return Trip',
            'route' => $form->travel_to . ' - ' . $form->travel_from,
            'date' => $form->return_date,
            'operator' => $form->return_operator,
            'flight' => $form->return_flight_vessel_no,
            'booking' => $form->return_booking_code,
            'atd' => $form->return_atd,
            'ata' => $form->return_ata,
        ]);
    }

    $section = \Modules\MasterManagement\Models\Section::with('department')->where('section_head_id', $form->employee_id)->first();
    $department = $section?->department
        ?? \Modules\MasterManagement\Models\Department::find($form->employee?->department_id)
        ?? \Modules\MasterManagement\Models\Department::where('dept_head_id', $form->employee_id)->first()
        ?? \Modules\MasterManagement\Models\Department::where('dept_admin_id', $form->employee_id)->first();
    $division = $department?->divisionHead
        ?? \Modules\MasterManagement\Models\MasterDivision::where('division_head_id', $form->employee_id)->first();
    $divisionNameByUser = fn ($userId) => \Modules\MasterManagement\Models\MasterDivision::where('division_head_id', $userId)->first()?->division_name;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>External Form Request #{{ $form->id }}</title>
    <style>
        @page { margin: 40px 60px 70px 60px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #000; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        .center { text-align: center; }
        .right { text-align: right; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 12px; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #000; padding-bottom: 3px; margin-bottom: 8px; }
        .grid td, .grid th { border: 1px solid #000; padding: 7px 8px; vertical-align: top; }
        .grid th { text-align: center; font-size: 10px; background: #d9d9d9; }
        .box { border: 1px solid #000; }
        .box-head { background: #d9d9d9; border-bottom: 1px solid #000; padding: 6px 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .box-body { padding: 8px 10px; }
        .kv td { padding: 4px 0; vertical-align: top; }
        .kv td.lbl { font-weight: bold; }
        .kv td.sep { width: 3%; text-align: center; }
        .trips td.trip { width: 50%; padding: 0 6px; vertical-align: top; }
        .trips td.trip:first-child { padding-left: 0; }
        .trips td.trip:last-child { padding-right: 0; }
        .footer { position: fixed; bottom: -48px; left: 0; right: 0; border-top: 1px solid #000; padding-top: 5px; font-size: 9px; }
    </style>
</head>

<body>
    <table>
        <tr>
            <td style="font-size: 14px; font-weight: bold;">{{ config('company.name') }}<br><span style="font-size: 12px;">{{ config('company.plant') }}</span></td>
            <td class="right" style="vertical-align: middle; width: 1%;"><img src="{{ public_path('img/logo-eco.png') }}" style="height: 80px;" alt="Logo"></td>
        </tr>
    </table>
    <div class="center" style="font-size: 15px; font-weight: bold; text-decoration: underline; margin: 30px 0 26px;">EXTERNAL TRAVEL PURCHASE REQUEST</div>

    <table style="margin-bottom: 8px;">
        <tr><td></td><td class="right">Date : &nbsp;<u>{{ $date($form->created_at) }}</u></td></tr>
    </table>

    <div class="section">
        <div class="box">
            <div class="box-head">Transport Information</div>
            <div class="box-body">
                <table class="kv">
                    <tr><td class="lbl" style="width: 25%">Transport Mode</td><td class="sep">:</td><td style="width: 22%">{{ $val($transportMode) }}</td><td class="lbl" style="width: 25%">Trip Type</td><td class="sep">:</td><td style="width: 22%">{{ $val(ucwords(str_replace('_', ' ', $form->trip_type))) }}</td></tr>
                    <tr><td class="lbl">Vendor / Customer</td><td class="sep">:</td><td colspan="4">{{ $val($form->vendor_customer) }}</td></tr>
                    <tr><td class="lbl">Purpose</td><td class="sep">:</td><td colspan="4">{{ $val($form->purpose) }}</td></tr>
                    <tr><td class="lbl">Travel From</td><td class="sep">:</td><td>{{ $val($form->travel_from) }}</td><td class="lbl">Travel To</td><td class="sep">:</td><td>{{ $val($form->travel_to) }}</td></tr>
                    <tr><td class="lbl">Departure Date</td><td class="sep">:</td><td>{{ $date($form->departure_date) }}</td>@if ($isRoundTrip)<td class="lbl">Return Date</td><td class="sep">:</td><td>{{ $date($form->return_date) }}</td>@else<td colspan="3"></td>@endif</tr>
                </table>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Trip Detail</div>
        <table class="trips">
            <tr>
                @foreach ($trips as $trip)
                    <td class="trip">
                        <div class="box">
                            <div class="box-head">{{ $trip['title'] }}</div>
                            <div class="box-body">
                                <table class="kv">
                                    <tr><td class="lbl" style="width: 42%">Route</td><td class="sep">:</td><td>{{ $val($trip['route']) }}</td></tr>
                                    <tr><td class="lbl">Travel Date</td><td class="sep">:</td><td>{{ $date($trip['date']) }}</td></tr>
                                    <tr><td class="lbl">{{ $operatorLabel }}</td><td class="sep">:</td><td>{{ $val($trip['operator']) }}</td></tr>
                                    <tr><td class="lbl">{{ $flightLabel }}</td><td class="sep">:</td><td>{{ $val($trip['flight']) }}</td></tr>
                                    <tr><td class="lbl">Booking Code</td><td class="sep">:</td><td>{{ $val($trip['booking']) }}</td></tr>
                                    <tr><td class="lbl">ATD</td><td class="sep">:</td><td>{{ $dateTime($trip['atd']) }}</td></tr>
                                    <tr><td class="lbl">ATA</td><td class="sep">:</td><td>{{ $dateTime($trip['ata']) }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Traveler Information</div>
        <div class="box">
            <div class="box-body">
                @forelse ($form->travelers as $traveler)<div>{{ $loop->iteration }}. {{ $val($traveler->name) }}</div>@empty<div>-</div>@endforelse
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Remarks</div>
        <div class="box">
            <div class="box-body">{{ $val($form->remark) }}</div>
        </div>
    </div>

    <div class="section" style="page-break-before: always;">
        <div class="section-title">Approval &amp; Processing</div>
        <table class="grid">
            <thead>
                <tr><th style="width: 6%">No</th><th style="width: 24%">Role</th><th style="width: 23%">Name</th><th style="width: 22%">Date &amp; Time</th><th style="width: 25%">Status</th></tr>
            </thead>
            <tbody>
                @forelse ($approvals as $step)
                    @php
                        $isPending = $step->status === 'pending';
                        $role = $step->role?->name ?? '-';

                        $organization = match ($role) {
                            'Sect Head' => $department?->name,
                            'Dept Head' => $department?->name,
                            'Div Head' => $divisionNameByUser($step->user_id) ?? $division?->division_name,
                            default => null,
                        };

                        $roleLabel = $organization
                            ? "{$organization} (" . str_replace(' ', '.', $role) . ')'
                            : ($step->label ?: str_replace(' ', '.', $role));

                        $statusLabel = match ($step->status) { 'submitted' => 'Requested', 'approved' => 'Approved', 'reviewed' => 'Reviewed', default => $step->action_type === 'check' ? 'Awaiting Review' : 'Awaiting Approval' };
                        $statusStyle = match ($step->status) { 'submitted' => 'background-color: #DBEAFE; color: #1D4ED8;', 'approved', 'reviewed' => 'background-color: #DCFCE7; color: #15803D;', default => 'background-color: #FEF3C7; color: #92400E;' };
                    @endphp
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $val($roleLabel) }}</td>
                        <td>{{ $isPending ? '-' : $val($step->user?->name) }}</td>
                        <td class="center">{{ $step->acted_at ? $dateTime($step->acted_at) : '-' }}</td>
                        <td class="center"><span style="display: inline; padding: 3px 6px; border-radius: 4px; font-weight: bold; {{ $statusStyle }}">{{ $statusLabel }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td class="center">1</td>
                        <td>-</td>
                        <td>{{ $val($form->employee?->name) }}</td>
                        <td class="center">{{ $dateTime($form->created_at) }}</td>
                        <td class="center"><span style="display: inline; padding: 3px 6px; border-radius: 4px; font-weight: bold; background-color: #E5E7EB; color: #374151;">Draft Saved</span></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <table><tr><td>No. {{ $val($documentNo) }}</td><td class="right">Generated: {{ now()->format('d M Y H:i') }}</td></tr></table>
    </div>
</body>
</html>