@php
    use Modules\MasterManagement\Models\Section;
    use Modules\MasterManagement\Models\Department;
    use Modules\MasterManagement\Models\MasterDivision;
    use Modules\UserManagement\Models\User;

    $val = fn ($v) => ($v === null || $v === '') ? '-' : $v;
    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y') : '-';
    $dateTime = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y H:i') : '-';
    $money = fn ($amount, $currency) => ((float) $amount) ? number_format((float) $amount, 2) . ' ' . ($currency ?? '') : 'Not Applicable';

    $requesterId = $order->user_id;

    $section = Section::with('department')
        ->where('section_head_id', $requesterId)
        ->first();

    $department = $order->department
        ?? $section?->department
        ?? Department::where('dept_head_id', $requesterId)->first()
        ?? Department::where('dept_admin_id', $requesterId)->first();

    $division = $department?->divisionHead
        ?? MasterDivision::where('division_head_id', $requesterId)->first();

        $position = $division && (int) $division->division_head_id === (int) $requesterId
    ? 'Div Head'
    : ($department && (int) $department->dept_head_id === (int) $requesterId
        ? 'Dept Head'
        : ($section && (int) $section->section_head_id === (int) $requesterId
            ? 'Sect Head'
            : '-'));

    $level = $val(User::where('id', $requesterId)->value('grade'));

    $isGroup = $order->trip_type === 'group';
    $advance = $order->advance;

    $approvalLabels = [
        'submitted' => 'Requested',
        'pending' => 'Awaiting Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        'revision' => 'Revision Required',
    ];

    $tripTypes = [
        'individual' => 'Business Trip - Individual',
        'group' => 'Business Trip - Group Trip',
        'annual' => 'Annual Trip',
    ];

    $summary = [
        [['Request Type', $tripTypes[$order->trip_type] ?? 'Business Trip - Individual'], ['Travel Region', $val($order->travel_region)]],
        [['Employee', $val($order->user?->name)], ['Country', $val($order->country)]],
        [['Position', $position], ['Level', $level]],
        [['Section', $val($section?->name)], ['Route', $val($order->travel_from) . ' → ' . $val($order->travel_to)]],
        [['Department', $val($department?->name)], ['Departure', $date($order->departure_date) . ' ' . $order->departure_time]],
        [['Purpose', $val($order->purpose)], ['Return', $date($order->return_date) . ' ' . $order->return_time]],
        [['Remarks', $val($order->remarks)]],
    ];

    $employeeInfo = [
        [['Employee', $val($order->user?->name)]],
        [['Section', $val($section?->name)]],
        [['Department', $val($department?->name)]],
    ];

    $tripInfo = [
        [['Request Type', $tripTypes[$order->trip_type] ?? 'Business Trip - Individual'], ['Travel Region', $val($order->travel_region)]],
        [['Country', $val($order->country)], ['Route', $val($order->travel_from) . ' → ' . $val($order->travel_to)]],
        [['Departure', $date($order->departure_date) . ' ' . $order->departure_time], ['Return', $date($order->return_date) . ' ' . $order->return_time]],
        [['Purpose', $val($order->purpose)]],
        [['Remarks', $val($order->remarks)]],
    ];

    $ticket = [
        [['Ticket Type', $val($order->ticket_type)], ['Ticket Arrangement', $val($order->ticket_arrangement)]],
        [['Meal Allowance', $money($advance?->meal_allowance, $advance?->currency)], ['Accommodation', $val($order->accommodation_arrangement)]],
        [['Pocket Money', $money($advance?->pocket_money, $advance?->currency)]],
    ];

    $groupTripInfo = [
    [
        ['Request Type', 'Business Trip - Group'],
        ['Travel Region', $val($order->travel_region)],
    ],
    [
        ['Country', $val($order->country)],
        ['Route', $val($order->travel_from) . ' → ' . $val($order->travel_to)],
    ],
    [
        ['Purpose', $val($order->purpose)],
        ['Departure', $date($order->departure_date) . ' ' . $order->departure_time],
    ],
    [
        ['Remarks', $val($order->remarks)],
        ['Return', $date($order->return_date) . ' ' . $order->return_time],
    ],
];

    $otherMembers = collect();

    if ($isGroup) {
        $order->loadMissing('group.members.user');

        $otherMembers = ($order->group?->members ?? collect())
            ->reject(fn ($member) => (int) $member->user_id === (int) $requesterId)
            ->values();

        $memberIds = $otherMembers->pluck('user_id')->all();

        $bySection = Section::with('department:id,name')
            ->whereIn('section_head_id', $memberIds)
            ->get()
            ->mapWithKeys(fn ($s) => [$s->section_head_id => $s->department?->name]);

        $byDeptHead = Department::whereIn('dept_head_id', $memberIds)
            ->pluck('name', 'dept_head_id');

        $byDeptAdmin = Department::whereIn('dept_admin_id', $memberIds)
            ->pluck('name', 'dept_admin_id');

        $otherMembers = $otherMembers->map(fn ($member) => [
            'name' => $member->user?->name ?? '-',
            'department' => $bySection[$member->user_id]
                ?? $byDeptHead[$member->user_id]
                ?? $byDeptAdmin[$member->user_id]
                ?? 'No Department',
        ])->sortBy([['department', 'asc'], ['name', 'asc']])->values();
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Travel Order {{ $order->order_number }}</title>
    <style>
        @page { margin: 40px 75px 70px 75px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #000; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        .center { text-align: center; }
        .right { text-align: right; }
        .doc-head { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 22px; }
        .doc-title { font-size: 20px; font-weight: bold; letter-spacing: 3px; }
        .doc-sub { font-size: 10px; margin-top: 2px; }
        .doc-no { font-size: 11px; font-weight: bold; margin-top: 8px; }
        .section { margin-bottom: 22px; }
        .section-title { text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px; }
        .info { padding: 0 32px; }
        .row { margin-bottom: 7px; }
        .cell { float: left; width: 47%; }
        .cell.r { width: 53%; margin-left: -25px; }
        .cell.full { width: 100%; }
        .lbl { float: left; width: 105px; }
        .val { margin-left: 105px; font-weight: bold; padding-right: 8px; }
        .clr { clear: both; }
        .grid td, .grid th { border: 1px solid #000; padding: 7px 8px; vertical-align: top; }
        .grid th { text-align: center; font-size: 10px; background: #d9d9d9; }
        .footer { position: fixed; bottom: -48px; left: 0; right: 0; border-top: 1px solid #000; padding-top: 5px; font-size: 9px; }
    </style>
</head>

<body>
    <div class="doc-head">
        <div style="text-align: left; font-size: 11px; font-weight: bold; letter-spacing: 0; margin-bottom: 18px;">
            PT. Ecogreen Oleochemicals<br>
            Batam Plant
        </div>
        <div class="doc-title">TRAVEL ORDER</div>
    </div>

    @if ($isGroup)
    <div class="section">
    <div class="info">
        <div class="row">
            <div class="cell" style="width: 47%;">
                <div class="lbl">Employee</div>
                <div class="val">{{ $val($order->user?->name) }}</div>
            </div>
            <div class="cell r">
                <div class="lbl">Position</div>
                <div class="val">{{ $position }}</div>
            </div>
            <div class="clr"></div>
        </div>

        <div class="row">
            <div class="cell" style="width: 47%;">
                <div class="lbl">Section</div>
                <div class="val">{{ $val($section?->name) }}</div>
            </div>
            <div class="cell r">
                <div class="lbl">Level</div>
                <div class="val">{{ $level }}</div>
            </div>
            <div class="clr"></div>
        </div>

        <div class="row">
            <div class="cell full">
                <div class="lbl">Department</div>
                <div class="val">{{ $val($department?->name) }}</div>
            </div>
            <div class="clr"></div>
        </div>
    </div>
</div>

        <div class="section">
            <div class="section-title">Trip Information</div>
            <div class="info">
            @foreach ($groupTripInfo as $row)
                    <div class="row">
                        @foreach ($row as [$l, $v])
                            <div class="cell {{ count($row) === 1 ? 'full' : ($loop->last ? 'r' : '') }}">
                                <div class="lbl">{{ $l }}</div>
                                <div class="val">{{ $v }}</div>
                            </div>
                        @endforeach
                        <div class="clr"></div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="section">
            <div class="section-title">Ticket, Accommodation &amp; Travel Advance</div>
            <div class="info">
                @foreach ($ticket as $row)
                    <div class="row">
                        @foreach ($row as [$l, $v])
                            <div class="cell {{ count($row) === 1 ? 'full' : ($loop->last ? 'r' : '') }}">
                                <div class="lbl">{{ $l }}</div>
                                <div class="val">{{ $v }}</div>
                            </div>
                        @endforeach
                        <div class="clr"></div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="section">
    <div class="section-title">Other Employees in Group Trip</div>
    <table class="grid">
        <thead>
            <tr>
            <th style="width: 6%">No</th>
                <th style="width: 52%; text-align: left;">Employee</th>
                <th style="width: 40%; text-align: center;">Department</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($otherMembers as $member)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td style="text-align: left;">{{ $member['name'] }}</td>
                    <td style="text-align: center;">{{ $member['department'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="center">No other employees in this group.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
    @else
        @foreach ([['Request Summary', $summary], ['Ticket, Accommodation & Travel Advance', $ticket]] as [$title, $rows])
            <div class="section">
                @if ($title !== 'Request Summary')
                    <div class="section-title">{{ $title }}</div>
                @endif
                <div class="info">
                    @foreach ($rows as $row)
                        <div class="row">
                            @foreach ($row as [$l, $v])
                                <div class="cell {{ count($row) === 1 ? 'full' : ($loop->last ? 'r' : '') }}">
                                    <div class="lbl">{{ $l }}</div>
                                    <div class="val">{{ $v }}</div>
                                </div>
                            @endforeach
                            <div class="clr"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif

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
                @forelse ($order->approvals->sortBy('sequence') as $step)
                    @php
                        $role = $step->role?->name ?? '-';

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
                            'approved' => 'background-color: #DCFCE7; color: #15803D;',
                            'pending' => 'background-color: #FEF3C7; color: #92400E;',
                            default => '',
                        };
                    @endphp
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $val($roleLabel) }}</td>
                        <td>{{ $val($step->user?->name) }}</td>
                        <td class="center">
                            {{ $step->acted_at ? $dateTime($step->acted_at) : '-' }}
                        </td>
                        <td class="center">
                            <span style="display: inline; padding: 3px 6px; border-radius: 4px; font-weight: bold; {{ $statusStyle }}">
                                {{ $approvalLabels[$step->status] ?? $step->status }}
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
                    {{ $order->approvals->where('status', '!=', 'submitted')->isNotEmpty() && $order->approvals->where('status', '!=', 'submitted')->every(fn ($step) => $step->status === 'approved') ? $val($order->order_number) : '-' }}
                </td>
                <td class="right">Generated: {{ now()->format('d M Y H:i') }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
