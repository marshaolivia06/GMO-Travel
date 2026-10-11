@php
    use Modules\MasterManagement\Models\Section;
    $section = Section::where('section_head_id', $order->user_id)->first();
    $val = fn ($v) => ($v === null || $v === '') ? '-' : $v;
    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y') : '-';
    $dateTime = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y H:i') : '-';
    $money = fn ($amount, $currency) => ((float) $amount) ? number_format((float) $amount, 2) . ' ' . ($currency ?? '') : 'Not Applicable';

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

    $advance = $order->advance;

    $summary = [
    [['Request Type', $tripTypes[$order->trip_type] ?? 'Business Trip - Individual'], ['Travel Region', $val($order->travel_region)]],
    [['Employee', $val($order->user?->name)], ['Country', $val($order->country)]],
    [['Section', $val($section?->name)], ['Route', $val($order->travel_from) . ' → ' . $val($order->travel_to)]],
    [['Department', $val($order->department?->name)], ['Departure', $date($order->departure_date) . ' ' . $order->departure_time]],
    [['Purpose', $val($order->purpose)], ['Return', $date($order->return_date) . ' ' . $order->return_time]],
    [['Remarks', $val($order->remarks)]],
    ];

    $ticket = [
    [['Ticket Type', $val($order->ferry_ticket_type)], ['Ticket Arrangement', $val($order->ferry_arrangement)]],
    [['Meal Allowance', $money($advance?->meal_allowance, $advance?->currency)], ['Accommodation', $val($order->accommodation_arrangement)]],
    [['Pocket Money', $money($advance?->pocket_money, $advance?->currency)]],
];
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
                        $statusStyle = match ($step->status) {
                            'submitted' => 'background-color: #DBEAFE; color: #1D4ED8;',
                            'approved' => 'background-color: #DCFCE7; color: #15803D;',
                            'pending' => 'background-color: #FEF3C7; color: #92400E;',
                            default => '',
                        };
                    @endphp
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $val($step->role?->name) }}</td>
                        <td>{{ $val($step->user?->name) }}</td>
                        <td class="center">{{ $step->acted_at ? $dateTime($step->acted_at) : '-' }}</td>
                        <td class="center">
                            <span style="display: inline; padding: 3px 6px; border-radius: 4px; font-weight: bold; {{ $statusStyle }}">{{ $approvalLabels[$step->status] ?? $step->status }}</span>
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
            <td>No. {{ $order->approvals->where('status', '!=', 'submitted')->isNotEmpty() && $order->approvals->where('status', '!=', 'submitted')->every(fn ($step) => $step->status === 'approved') ? $val($order->order_number) : '-' }}</td>
            <td class="right">Generated: {{ now()->format('d M Y H:i') }}</td>
        </tr>
    </table>
</div>
</body>
</html>