@php
    $val = fn ($v) => ($v === null || $v === '') ? '-' : $v;

    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y') : '-';

    $dateTime = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y H:i') : '-';

    $money = function ($amount, $currency) {
        $n = (float) $amount;

        return $n ? number_format($n, 2) . ' ' . ($currency ?? '') : 'Not Applicable';
    };

    $approvalLabels = [
        'submitted' => 'Submitted',
        'pending'   => 'Awaiting Approval',
        'approved'  => 'Approved',
        'rejected'  => 'Rejected',
        'cancelled' => 'Cancelled',
        'revision'  => 'Revision Required',
    ];

    $tripTypes = [
        'individual' => 'Business Trip - Individual',
        'group'      => 'Business Trip - Group Trip',
        'annual'     => 'Annual Trip',
    ];

    $advance = $order->advance;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Travel Order {{ $order->order_number }}</title>
    <style>
        @page { margin: 28px 36px 64px 36px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        table { width: 100%; border-collapse: collapse; }

        .header { border-bottom: 2px solid #1565c0; margin-bottom: 16px; }
        .header td { padding-bottom: 8px; vertical-align: top; }
        .title { font-size: 22px; font-weight: bold; }
        .subtitle { font-size: 9px; color: #777; }
        .right { text-align: right; }
        .order-number { font-size: 12px; font-weight: bold; }

        .section-title {
            background: #f1f1f1;
            border: 1px solid #ccc;
            padding: 5px 8px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .section { margin-bottom: 24px; }

        .grid td, .grid th {
            border: 1px solid #ccc;
            padding: 9px 10px;
            vertical-align: top;
        }

        .label { width: 17%; background: #fafafa; color: #666; }
        .value { width: 33%; font-weight: bold; }

        .grid th {
            background: #f1f1f1;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }

        .center { text-align: center; }

        .footer {
            position: fixed;
            bottom: -44px;
            left: 0;
            right: 0;
            border-top: 2px solid #9e9e9e;
            padding-top: 6px;
            font-size: 9px;
            color: #777;
        }
    </style>
</head>
<body>

    {{-- Kop dokumen --}}
    <table class="header">
        <tr>
            <td>
                <div class="title">TRAVEL ORDER</div>
                <div class="subtitle">Business Trip Request Form</div>
            </td>
            <td class="right">
                <div class="subtitle">Order Number</div>
                <div class="order-number">{{ $val($order->order_number) }}</div>
            </td>
        </tr>
    </table>

    {{-- Request Summary --}}
    <div class="section">
        <div class="section-title">Request Summary</div>
        <table class="grid">
            <tr>
                <td class="label">Request Type</td>
                <td class="value">{{ $tripTypes[$order->trip_type] ?? 'Business Trip - Individual' }}</td>
                <td class="label">Travel Region</td>
                <td class="value">{{ $val($order->travel_region) }}</td>
            </tr>
            <tr>
                <td class="label">Employee</td>
                <td class="value">{{ $val($order->user?->name) }}</td>
                <td class="label">Route</td>
                <td class="value">{{ $val($order->travel_from) }} &rarr; {{ $val($order->travel_to) }}</td>
            </tr>
            <tr>
                <td class="label">Department</td>
                <td class="value">{{ $val($order->department?->name) }}</td>
                <td class="label">Departure</td>
                <td class="value">{{ $date($order->departure_date) }} {{ $order->departure_time }}</td>
            </tr>
            <tr>
                <td class="label">Country</td>
                <td class="value">{{ $val($order->country) }}</td>
                <td class="label">Return</td>
                <td class="value">{{ $date($order->return_date) }} {{ $order->return_time }}</td>
            </tr>
            <tr>
                <td class="label">Purpose</td>
                <td class="value" colspan="3">{{ $val($order->purpose) }}</td>
            </tr>
            <tr>
                <td class="label">Remarks</td>
                <td class="value" colspan="3">{{ $val($order->remarks) }}</td>
            </tr>
        </table>
    </div>

    {{-- Ticket, Accommodation & Travel Advance --}}
    <div class="section">
        <div class="section-title">Ticket, Accommodation &amp; Travel Advance</div>
        <table class="grid">
            <tr>
                <td class="label">Ticket Type</td>
                <td class="value">{{ $val($order->ferry_ticket_type) }}</td>
                <td class="label">Ticket Arrangement</td>
                <td class="value">{{ $val($order->ferry_arrangement) }}</td>
            </tr>
            <tr>
                <td class="label">Accommodation</td>
                <td class="value">{{ $val($order->accommodation_arrangement) }}</td>
                <td class="label">Meal Allowance</td>
                <td class="value">{{ $money($advance?->meal_allowance, $advance?->currency) }}</td>
            </tr>
            <tr>
                <td class="label">Pocket Money</td>
                <td class="value" colspan="3">{{ $money($advance?->pocket_money, $advance?->currency) }}</td>
            </tr>
        </table>
    </div>

    {{-- Reviewed & Approved By (tabel) --}}
    <div class="section">
        <div class="section-title">Reviewed &amp; Approved By</div>
        <table class="grid">
        <thead>
                <tr>
                    <th class="center" style="width: 6%">No</th>
                    <th style="width: 24%">Role</th>
                    <th style="width: 28%">Name</th>
                    <th style="width: 22%">Date &amp; Time</th>
                    <th>Status</th>
                </tr>
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
                    <tr>
                        <td colspan="5" class="center">No approval data yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Footer: garis abu-abu, nama aplikasi di kiri, waktu cetak di kanan --}}
    <div class="footer">
        <table>
            <tr>
                <td>GMO - Travel</td>
                <td class="right">Generated: {{ now()->format('d M Y H:i') }}</td>
            </tr>
        </table>
    </div>

</body>
</html>