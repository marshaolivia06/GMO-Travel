@php
    use Modules\MasterManagement\Models\Section;
    use Modules\MasterManagement\Models\Department;
    use Modules\MasterManagement\Models\MasterDivision;
    use Modules\UserManagement\Models\User;

    $val = fn ($v) => ($v === null || $v === '') ? '-' : $v;
    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y') : '-';
    $dateTime = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y H:i') : '-';

    $statusLabels = [
        'awaiting_check_hr_admin' => 'Awaiting Check - HR Admin',
        'awaiting_approval_hr_supervisor' => 'Awaiting Approval - HR Supervisor',
        'awaiting_fare_reference_gmo' => 'Awaiting Fare Reference - GMO',
        'awaiting_ticket_processing' => 'Awaiting Ticket Processing',
        'processed_by_gmo' => 'Processed by GMO',
        'revision_required' => 'Revision Required',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    $employeeId = $ticket->employee_id;

    $section = Section::with('department')
        ->where('section_head_id', $employeeId)
        ->first();

    $department = $section?->department
        ?? Department::where('dept_head_id', $employeeId)->first()
        ?? Department::where('dept_admin_id', $employeeId)->first();

    $eligibleDestination = User::find($employeeId)?->eligibleDestination?->city_name;

    $division = MasterDivision::where('division_head_id', $employeeId)->first();

    $dates = $ticket->legs->map(fn ($leg) => $leg->reschedule_date ?? $leg->travel_date)->filter()->sort()->values();
    $travelPeriod = $dates->isEmpty()
        ? '-'
        : ($dates->count() > 1 ? $dates->first()->format('d M Y') . ' - ' . $dates->last()->format('d M Y') : $dates->first()->format('d M Y'));

    $arrangementLabel = $ticket->ticket_arrangement === 'Direct Payment'
        ? 'Direct Payment - Reimbursable'
        : 'Booked by Company - ' . ($ticket->route_condition === 'Different Route' ? 'Excess Potential' : 'Not Reimbursable');

    $departures = $ticket->legs->where('direction', 'departure')->values();
    $returns = $ticket->legs->where('direction', 'return')->values();
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Annual Ticket #{{ $ticket->id }}</title>
    <style>
        @page { margin: 40px 75px 70px 75px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #000; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        .center { text-align: center; }
        .right { text-align: right; }
        .doc-head { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 22px; }
        .doc-title { font-size: 20px; font-weight: bold; letter-spacing: 3px; }
        .section { margin-bottom: 22px; }
        .section-title { text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px; }
        .grid td, .grid th { border: 1px solid #000; padding: 7px 8px; vertical-align: top; }
        .grid th { text-align: center; font-size: 10px; background: #d9d9d9; }
        .info-table td { padding: 4px 6px; vertical-align: top; font-size: 11px; }
        .info-table td.lbl { width: 24%; white-space: nowrap; }
        .info-table td.val { font-weight: bold; }
        .sub-title { font-size: 11px; font-weight: bold; margin-bottom: 8px; }
        .footer { position: fixed; bottom: -48px; left: 0; right: 0; border-top: 1px solid #000; padding-top: 5px; font-size: 9px; }
    </style>
</head>

<body>
    <div class="doc-head">
        <div style="text-align: left; font-size: 11px; font-weight: bold; margin-bottom: 18px;">
            PT. Ecogreen Oleochemicals<br>
            Batam Plant
        </div>
        <div class="doc-title">ANNUAL TICKET</div>
    </div>

    <div class="section">
        <table class="info-table">
            <tr>
                <td class="lbl">Employee</td>
                <td class="val">{{ $val($ticket->employee?->name) }}</td>
                <td class="lbl">Department</td>
                <td class="val">{{ $val($department?->name) }}</td>
            </tr>
            <tr>
                <td class="lbl">Office Location</td>
                <td class="val">Batam</td>
                <td class="lbl">Eligible Travel Destination</td>
                <td class="val">{{ $val($eligibleDestination) }}</td>
            </tr>
            <tr>
                <td class="lbl">Travel Period</td>
                <td class="val">{{ $travelPeriod }}</td>
                <td class="lbl">Request Status</td>
                <td class="val">{{ $statusLabels[$ticket->status] ?? $val($ticket->status) }}</td>
            </tr>
            <tr>
                <td class="lbl">Annual Ticket Quota Year</td>
                <td class="val">{{ $val($ticket->quota_year) }}</td>
                <td class="lbl">Ticket Arrangement</td>
                <td class="val">{{ $arrangementLabel }}</td>
            </tr>
            <tr>
                <td class="lbl">Route Condition</td>
                <td class="val" colspan="3">{{ $val($ticket->route_condition) }}</td>
            </tr>
            @if ($ticket->remarks)
            <tr>
                <td class="lbl">Remarks</td>
                <td class="val" colspan="3">{{ $ticket->remarks }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">Travel Legs &amp; Fare Reference</div>

        <div class="sub-title">Departure</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 6%">No</th>
                    <th style="width: 22%; text-align: left;">Traveler</th>
                    <th style="width: 16%; text-align: center;">Type</th>
                    <th style="width: 18%; text-align: center;">Date</th>
                    <th style="width: 38%; text-align: center;">Route</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($departures as $leg)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td style="text-align: left;">{{ $val($leg->name) }}</td>
                        <td class="center">{{ $val($leg->relationship ?? ucfirst($leg->traveler_type)) }}</td>
                        <td class="center">{{ $date($leg->reschedule_date ?? $leg->travel_date) }}</td>
                        <td class="center">{{ $val($leg->travel_from) }} → {{ $val($leg->travel_to) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="center">No departure leg in this request.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="sub-title" style="margin-top: 14px;">Return</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 6%">No</th>
                    <th style="width: 22%; text-align: left;">Traveler</th>
                    <th style="width: 16%; text-align: center;">Type</th>
                    <th style="width: 18%; text-align: center;">Date</th>
                    <th style="width: 38%; text-align: center;">Route</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($returns as $leg)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td style="text-align: left;">{{ $val($leg->name) }}</td>
                        <td class="center">{{ $val($leg->relationship ?? ucfirst($leg->traveler_type)) }}</td>
                        <td class="center">{{ $date($leg->reschedule_date ?? $leg->travel_date) }}</td>
                        <td class="center">{{ $val($leg->travel_from) }} → {{ $val($leg->travel_to) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="center">No return leg in this request.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Reviewed &amp; Approved By</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 6%">No</th>
                    <th style="width: 24%">Role</th>
                    <th style="width: 23%">Name</th>
                    <th class="center" style="width: 22%">Date &amp; Time</th>
                    <th class="center" style="width: 25%">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($approvals->sortBy('sequence') as $step)
                    @php
                        $role = $step->role instanceof \Spatie\Permission\Models\Role ? $step->role->name : $step->role;

                        $organization = match ($role) {
                            'Sect Head' => $section?->name,
                            'Dept Head' => $department?->name,
                            'Div Head' => $division?->division_name,
                            default => null,
                        };

                        $displayRole = str_replace(' ', '.', $role);
                        $roleLabel = $organization
                            ? "{$organization} ({$displayRole})"
                            : $displayRole;

                        $statusStyle = match ($step->status) {
                            'submitted' => 'background-color: #DBEAFE; color: #1D4ED8;',
                            'approved', 'checked' => 'background-color: #DCFCE7; color: #15803D;',
                            'revision', 'pending' => 'background-color: #FEF9C3; color: #A16207;',
                            default => '',
                        };

                        $statusLabel = match ($step->status) {
                            'submitted' => 'Requested',
                            'checked' => 'Checked',
                            'approved' => 'Approved',
                            'revision' => 'Revision',
                            'rejected' => 'Rejected',
                            'pending' => ($step->action_type ?? 'approve') === 'check' ? 'Awaiting Check' : 'Awaiting Approval',
                            default => $step->status,
                        };
                    @endphp
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $val($roleLabel) }}</td>
                        <td>{{ $val($step->name) }}</td>
                        <td class="center">{{ $dateTime($step->acted_at) }}</td>
                        <td class="center">
                            <span style="display: inline; padding: 3px 6px; border-radius: 4px; font-weight: bold; {{ $statusStyle }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="center">No approval data yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <table>
            <tr>
                <td>
                    No.
                    @php
                        // Nomor dokumen muncul begitu step terakhir pada round terakhir selesai diproses
                        $lastStage = $approvals->sortBy('sequence')->last();
                        $documentNo = $lastStage && in_array($lastStage->status, ['checked', 'approved'], true)
                            ? $ticket->document_number
                            : '-';
                    @endphp
                    {{ $val($documentNo) }}
                </td>
                <td class="right">Generated: {{ now()->format('d M Y H:i') }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
