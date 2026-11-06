@php
    $val = fn ($v) => ($v === null || $v === '') ? '-' : $v;
    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d-M-Y') : '-';
    $dateTime = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y H:i') : '-';
    $steps = $approvals->where('step', '>', 0);
    $documentNo = $steps->isNotEmpty() && $steps->every(fn ($step) => in_array($step->status, ['approved', 'reviewed'])) ? $form->document_number : null;
    $segments = collect([['date' => $form->departure_date, 'route' => $form->travel_from . '-' . $form->travel_to, 'etd' => $form->departure_etd ?? null, 'eta' => $form->departure_eta ?? null, 'purpose' => $form->purpose, 'remark' => $form->remark]]);
    if ($form->trip_type !== 'one_way') $segments->push(['date' => $form->return_date, 'route' => $form->travel_to . '-' . $form->travel_from, 'etd' => $form->return_etd ?? null, 'eta' => $form->return_eta ?? null, 'purpose' => null, 'remark' => $form->return_remark ?? null]);
    $rows = max($form->travelers->count(), 1);

    // Hierarki organisasi requester untuk label Role di tabel approval (Sect/Dept/Div Head)
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
    <title>Air Ticket Purchase Request #{{ $form->id }}</title>
    <style>
        @page { margin: 40px 60px 70px 60px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #000; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        .center { text-align: center; }
        .right { text-align: right; }
        .section { margin-bottom: 22px; }
        .section-title { text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px; }
        .grid td, .grid th { border: 1px solid #000; padding: 7px 8px; vertical-align: middle; }
        .grid th { text-align: center; font-size: 10px; }
        .flight td { height: 16px; }
        .spacer td { height: 18px; }
        .detail td { padding: 12px 4px; vertical-align: top; font-size: 11px; }
        .detail td.lbl { width: 30%; font-weight: bold; }
        .detail td.sep { width: 3%; }
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
    <div class="center" style="font-size: 15px; font-weight: bold; text-decoration: underline; margin: 30px 0 26px;">AIR TICKET PURCHASE REQUEST</div>

    <table style="margin-bottom: 8px;">
        <tr><td></td><td class="right">Date : &nbsp;<u>{{ $date($form->created_at) }}</u></td></tr>
    </table>

    <table class="detail">
        @if ($form->operator)<tr><td class="lbl">Operator</td><td class="sep">:</td><td>{{ $form->operator }}</td></tr>@endif
        @if ($form->flight_vessel_no)<tr><td class="lbl">Flight / Vessel No</td><td class="sep">:</td><td>{{ $form->flight_vessel_no }}</td></tr>@endif
        <tr><td class="lbl">Booking Code</td><td class="sep">:</td><td>{{ $val($form->departure_booking_code) }}</td></tr>
        @if ($form->departure_atd)<tr><td class="lbl">Departure Time (ATD)</td><td class="sep">:</td><td>{{ $dateTime($form->departure_atd) }}</td></tr>@endif
        @if ($form->departure_ata)<tr><td class="lbl">Arrival Time (ATA)</td><td class="sep">:</td><td>{{ $dateTime($form->departure_ata) }}</td></tr>@endif
        @if ($form->return_operator)<tr><td class="lbl">Return Operator</td><td class="sep">:</td><td>{{ $form->return_operator }}</td></tr>@endif
        @if ($form->return_flight_vessel_no)<tr><td class="lbl">Return Flight / Vessel No</td><td class="sep">:</td><td>{{ $form->return_flight_vessel_no }}</td></tr>@endif
        @if ($form->trip_type === 'round_trip')<tr><td class="lbl">Return Booking Code</td><td class="sep">:</td><td>{{ $val($form->return_booking_code) }}</td></tr>@endif
        @if ($form->return_atd)<tr><td class="lbl">Return Departure (ATD)</td><td class="sep">:</td><td>{{ $dateTime($form->return_atd) }}</td></tr>@endif
        @if ($form->return_ata)<tr><td class="lbl">Return Arrival (ATA)</td><td class="sep">:</td><td>{{ $dateTime($form->return_ata) }}</td></tr>@endif
    </table>

    <div class="section">
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 13%">DATE</th><th style="width: 25%">NAME</th><th style="width: 11%">ROUTE</th><th style="width: 8%">ETD</th><th style="width: 8%">ETA</th><th style="width: 19%">PURPOSE</th><th style="width: 16%">REMARKS</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($segments as $segment)
                    <tr class="spacer"><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    @forelse ($form->travelers as $traveler)
                        <tr class="flight">
                            @if ($loop->first)<td class="center" rowspan="{{ $rows }}">{{ $date($segment['date']) }}</td>@endif
                            <td>{{ $val($traveler->name) }}</td>
                            @if ($loop->first)
                                <td class="center" rowspan="{{ $rows }}">{{ $val($segment['route']) }}</td>
                                <td class="center" rowspan="{{ $rows }}">{{ $val($segment['etd']) }}</td>
                                <td class="center" rowspan="{{ $rows }}">{{ $val($segment['eta']) }}</td>
                                <td class="center" rowspan="{{ $rows }}">{{ $val($segment['purpose']) }}</td>
                                <td class="center" rowspan="{{ $rows }}">{{ $val($segment['remark']) }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr class="flight"><td class="center">{{ $date($segment['date']) }}</td><td>-</td><td class="center">{{ $val($segment['route']) }}</td><td class="center">{{ $val($segment['etd']) }}</td><td class="center">{{ $val($segment['eta']) }}</td><td class="center">{{ $val($segment['purpose']) }}</td><td class="center">{{ $val($segment['remark']) }}</td></tr>
                    @endforelse
                @endforeach
                <tr class="spacer"><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            </tbody>
        </table>
    </div>

    <div class="section">
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
        'Sect Head' => $section?->name,
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