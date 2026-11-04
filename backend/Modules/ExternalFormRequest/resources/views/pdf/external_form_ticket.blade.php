@php
    $val = fn ($v) => ($v === null || $v === '') ? '-' : $v;
    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y') : '-';
    $dateTime = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y H:i') : '-';

    // Nomor dokumen baru tampil setelah seluruh step approval selesai diproses
    $steps = $approvals->where('step', '>', 0);
    $documentNo = $steps->isNotEmpty() && $steps->every(fn ($step) => in_array($step->status, ['approved', 'reviewed']))
        ? $form->document_number
        : null;

    // One way tidak punya tanggal kembali
    $returnDate = $form->trip_type === 'one_way' ? 'Not Specified' : $date($form->return_date);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>External Form Request #{{ $form->id }}</title>
    <style>
        @page { margin: 40px 75px 70px 75px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #000; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        .center { text-align: center; }
        .right { text-align: right; }
        .doc-head { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 22px; }
        .doc-title { font-size: 15px; font-weight: bold; letter-spacing: 2px; }
        .section { margin-bottom: 22px; }
        .section-title { text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px; }
        .grid td, .grid th { border: 1px solid #000; padding: 7px 8px; vertical-align: top; }
        .grid th { text-align: center; font-size: 10px; background: #d9d9d9; }
        .info-table td { padding: 4px 6px; vertical-align: top; font-size: 11px; }
        .info-table td.lbl { width: 24%; white-space: nowrap; }
        .info-table td.val { font-weight: bold; }
        .footer { position: fixed; bottom: -48px; left: 0; right: 0; border-top: 1px solid #000; padding-top: 5px; font-size: 9px; }
        .footer-note { text-align: center; font-size: 9px; font-style: italic; margin-bottom: 4px; }
    </style>
</head>

<body>
    <div class="doc-head">
        <div style="text-align: left; font-size: 11px; font-weight: bold; margin-bottom: 18px;">
            {{ config('company.name') }}<br>
            {{ config('company.plant') }}
        </div>
        <div class="doc-title">EXTERNAL TRAVEL PURCHASE REQUEST</div>
    </div>

    <div class="section">
        <div class="section-title">Travel Information</div>
        <table class="info-table">
            <tr>
                <td class="lbl">Document Number</td>
                <td class="val">{{ $val($documentNo) }}</td>
                <td class="lbl">Transport Mode</td>
                <td class="val">{{ $val($form->transport_mode) }}</td>
            </tr>
            <tr>
                <td class="lbl">Vendor / Customer</td>
                <td class="val">{{ $val($form->vendor_customer) }}</td>
                <td class="lbl">Purpose</td>
                <td class="val">{{ $val($form->purpose) }}</td>
            </tr>
            <tr>
                <td class="lbl">Travel From</td>
                <td class="val">{{ $val($form->travel_from) }}</td>
                <td class="lbl">Departure Date</td>
                <td class="val">{{ $date($form->departure_date) }}</td>
            </tr>
            <tr>
                <td class="lbl">Travel To</td>
                <td class="val">{{ $val($form->travel_to) }}</td>
                <td class="lbl">Return Date</td>
                <td class="val">{{ $returnDate }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Traveller Information</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 10%">No</th>
                    <th style="width: 90%; text-align: left;">Name</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($form->travelers as $traveler)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td style="text-align: left;">{{ $val($traveler->name) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="center">No traveller in this request.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 100%; text-align: left;">Remark</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: left;">{{ $val($form->remark) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Approval &amp; Processing</div>
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
                @forelse ($approvals as $step)
                    @php
                        $isPending = $step->status === 'pending';
                        $roleLabel = $step->label ?: str_replace(' ', '.', $step->role?->name ?? '-');
                        $statusLabel = match ($step->status) {
                            'submitted' => 'Requested',
                            'approved' => 'Approved',
                            'reviewed' => 'Reviewed',
                            default => $step->action_type === 'check' ? 'Awaiting Review' : 'Awaiting Approval',
                        };
                        $statusStyle = match ($step->status) {
                            'submitted' => 'background-color: #DBEAFE; color: #1D4ED8;',
                            'approved', 'reviewed' => 'background-color: #DCFCE7; color: #15803D;',
                            default => 'background-color: #FEF3C7; color: #92400E;',
                        };
                    @endphp
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $val($roleLabel) }}</td>
                        <td>{{ $isPending ? '-' : $val($step->user?->name) }}</td>
                        <td class="center">{{ $step->acted_at ? $dateTime($step->acted_at) : '-' }}</td>
                        <td class="center">
                            <span style="display: inline; padding: 3px 6px; border-radius: 4px; font-weight: bold; {{ $statusStyle }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="center">1</td>
                        <td>-</td>
                        <td>{{ $val($form->employee?->name) }}</td>
                        <td class="center">{{ $dateTime($form->created_at) }}</td>
                        <td class="center">
                            <span style="display: inline; padding: 3px 6px; border-radius: 4px; font-weight: bold; background-color: #E5E7EB; color: #374151;">
                                Draft Saved
                            </span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <table>
            <tr>
                <td>No. {{ $val($documentNo) }}</td>
                <td class="right">Generated: {{ now()->format('d M Y H:i') }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
