@php
    $val = fn ($v) => ($v === null || $v === '') ? '-' : $v;
    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y') : '-';
    $dateTime = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y H:i') : '-';
    $money = fn ($amount, $currency) => ((float) $amount) ? number_format((float) $amount, 2) . ' ' . ($currency ?? '') : 'Not Applicable';

    $approvalLabels = ['submitted' => 'Requested', 'pending' => 'Awaiting Approval', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled', 'revision' => 'Revision Required'];
    $tripTypes = ['individual' => 'Business Trip - Individual', 'group' => 'Business Trip - Group Trip', 'annual' => 'Annual Trip'];

    $advance = $order->advance;

    // Tiap baris = 1 atau 2 item [label, nilai]. 2 item => kiri-kanan (50:50), 1 item => selebar penuh.
    $summary = [
    [['Request Type', $tripTypes[$order->trip_type] ?? 'Business Trip - Individual'], ['Travel Region', $val($order->travel_region)]],
    [['Employee', $val($order->user?->name)], ['Country', $val($order->country)]],
    [['Department', $val($order->department?->name)], ['Route', $val($order->travel_from) . ' → ' . $val($order->travel_to)]],
    [['Purpose', $val($order->purpose)], ['Departure', $date($order->departure_date) . ' ' . $order->departure_time]],
    [['Remarks', $val($order->remarks)], ['Return', $date($order->return_date) . ' ' . $order->return_time]],
];

    $ticket = [
        [['Ticket Type', $val($order->ferry_ticket_type)], ['Ticket Arrangement', $val($order->ferry_arrangement)]],
        [['Accommodation', $val($order->accommodation_arrangement)], ['Meal Allowance', $money($advance?->meal_allowance, $advance?->currency)]],
        [['Pocket Money', $money($advance?->pocket_money, $advance?->currency)]],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Travel Order {{ $order->order_number }}</title>
    <style>
        @page { margin: 40px 50px 70px 50px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #000; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        .center { text-align: center; }
        .right { text-align: right; }

        /* Kop dokumen */
        .doc-head { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 22px; }
        .doc-title { font-size: 20px; font-weight: bold; letter-spacing: 3px; }
        .doc-sub { font-size: 10px; margin-top: 2px; }
        .doc-no { font-size: 11px; font-weight: bold; margin-top: 8px; }

        /* Judul section: tengah, bold, tanpa kotak */
        .section { margin-bottom: 22px; }
        .section-title { text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px; }

        /* Info 2 kolom: label & nilai sejajar rapi */
        .info { padding: 0 32px; }
        .row { margin-bottom: 7px; }
        .cell { float: left; width: 47%; }
        .cell.r { width: 53%; }
        .cell.full { width: 100%; }
        .lbl { float: left; width: 105px; }
        .val { margin-left: 105px; font-weight: bold; padding-right: 8px; }
        .clr { clear: both; }

        /* Tabel hanya untuk approval */
        .grid td, .grid th { border: 1px solid #000; padding: 7px 8px; vertical-align: top; }
        .grid th { text-align: center; font-size: 10px; background: #d9d9d9; }

        .footer { position: fixed; bottom: -48px; left: 0; right: 0; border-top: 1px solid #000; padding-top: 5px; font-size: 9px; }
    </style>
</head>
<body>

    {{-- Kop dokumen --}}
    <div class="doc-head">
        <div class="doc-title">TRAVEL ORDER</div>
        <div class="doc-sub">Business Trip Request Form</div>
        <div class="doc-no">No. {{ $val($order->order_number) }}</div>
    </div>

    {{-- Request Summary & Ticket (teks, 2 kolom) --}}
    @foreach ([['Request Summary', $summary], ['Ticket, Accommodation & Travel Advance', $ticket]] as [$title, $rows])
        <div class="section">
            <div class="section-title">{{ $title }}</div>
            <div class="info">
            @foreach ($rows as $row)
    <div class="row">@foreach ($row as [$l, $v])<div class="cell {{ count($row) === 1 ? 'full' : ($loop->last ? 'r' : '') }}"><div class="lbl">{{ $l }}</div><div class="val">{{ $v }}</div></div>@endforeach<div class="clr"></div></div>
@endforeach
            </div>
        </div>
    @endforeach

    {{-- Reviewed & Approved By (tetap tabel) --}}
    <div class="section">
        <div class="section-title">Reviewed &amp; Approved By</div>
        <table class="grid">
            <thead>
                <tr><th style="width: 6%">No</th><th style="width: 24%">Role</th><th style="width: 28%">Name</th><th style="width: 22%">Date &amp; Time</th><th>Status</th></tr>
            </thead>
            <tbody>
                @forelse ($order->approvals->sortBy('sequence') as $step)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $val($step->role?->name) }}</td>
                        <td>{{ $val($step->user?->name) }}</td>
                        <td>{{ $step->acted_at ? $dateTime($step->acted_at) : '-' }}</td>
                        <td>{{ $approvalLabels[$step->status] ?? $step->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="center">No approval data yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <table><tr><td>GMO - Travel</td><td class="right">Generated: {{ now()->format('d M Y H:i') }}</td></tr></table>
    </div>

</body>
</html>