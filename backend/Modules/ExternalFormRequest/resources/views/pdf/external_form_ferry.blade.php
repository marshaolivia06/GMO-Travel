@php
    $val = fn ($v) => ($v === null || $v === '') ? '-' : $v;
    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d-M-Y') : '-';
    $dateTime = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y H:i') : '-';
    $steps = $approvals->where('step', '>', 0);
    $documentNo = $steps->isNotEmpty() && $steps->every(fn ($step) => in_array($step->status, ['approved', 'reviewed'])) ? $form->document_number : null;
    $guests = $form->travelers->pluck('name')->filter()->implode(', ');
    $ticketCount = $form->travelers->count();
    $ticketType = $form->trip_type === 'one_way' ? 'One Way Ticket' : 'Two Way Open Ticket PP';
    $ticketRoute = $form->travel_from . '-' . $form->travel_to;
    $bookingCodes = collect([$form->departure_booking_code ?? null, $form->return_booking_code ?? null])->filter();
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ferry Ticket Purchase Request #{{ $form->id }}</title>
    <style>
        @page { margin: 40px 60px 70px 60px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #000; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        .center { text-align: center; }
        .right { text-align: right; }
        .section { margin-bottom: 22px; }
        .section-title { text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px; }
        .grid td, .grid th { border: 1px solid #000; padding: 7px 8px; vertical-align: top; }
        .grid th { text-align: center; font-size: 10px; background: #d9d9d9; }
        .detail td { padding: 12px 4px; vertical-align: top; font-size: 11px; }
        .detail td.lbl { width: 20%; font-weight: bold; }
        .detail td.sep { width: 3%; }
        .footer { position: fixed; bottom: -48px; left: 0; right: 0; border-top: 1px solid #000; padding-top: 5px; font-size: 9px; }
    </style>
</head>

<body>
    <div style="font-size: 14px; font-weight: bold;">{{ config('company.name') }}<br><span style="font-size: 12px;">{{ config('company.plant') }}</span></div>
    <div class="center" style="font-size: 15px; font-weight: bold; text-decoration: underline; margin: 30px 0 26px;">FERRY TICKET PURCHASE REQUEST</div>

    <table style="margin-bottom: 18px;">
        <tr><td></td><td class="right">Date : &nbsp;<u>{{ $date($form->created_at) }}</u></td></tr>
    </table>

    <table class="detail">
        <tr><td class="lbl">Name of Guest</td><td class="sep">:</td><td>{{ $val($guests) }}</td></tr>
        <tr><td class="lbl">Purpose</td><td class="sep">:</td><td>{{ $val($form->purpose) }}</td></tr>
        <tr><td class="lbl">No of Ticket</td><td class="sep">:</td><td>{{ $ticketCount }} pc {{ $ticketType }} {{ $ticketRoute }}</td></tr>
        <tr>
            <td class="lbl">Booking Code</td><td class="sep">:</td>
            <td>@forelse ($bookingCodes as $code){{ $code }}<br>@empty - @endforelse</td>
        </tr>
        <tr><td class="lbl">Remarks</td><td class="sep">:</td><td>{{ $val($form->remark) }}</td></tr>
    </table>

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
                        $roleLabel = $step->label ?: str_replace(' ', '.', $step->role?->name ?? '-');
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