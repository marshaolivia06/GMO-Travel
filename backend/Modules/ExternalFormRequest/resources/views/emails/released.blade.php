<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 14px; color: #222; }
        .container { max-width: 640px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
        .header { background: #f3f4f6; padding: 16px 24px; border-bottom: 1px solid #e5e7eb; }
        .header h2 { margin: 0; font-size: 16px; }
        .content { padding: 24px; line-height: 1.6; }
        .info { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .info td { padding: 6px 4px; vertical-align: top; }
        .info td.lbl { width: 35%; font-weight: bold; }
        .footer { padding: 12px 24px; background: #f9fafb; font-size: 12px; color: #6b7280; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>External Travel Purchase Request — Released</h2>
        </div>
        <div class="content">
            <p>Dear {{ $form->employee?->name ?? 'Requester' }},</p>

            @if (! empty($emailMessage))
                <p>{!! $emailMessage !!}</p>
            @endif

            <table class="info">
                <tr><td class="lbl">Document Number</td><td>{{ $form->document_number ?? '-' }}</td></tr>
                <tr><td class="lbl">Request Type</td><td>{{ ucwords(str_replace('_', ' ', $form->type)) }}</td></tr>
                @if ($form->type === 'airplane_ferry')
                    <tr><td class="lbl">Route</td><td>{{ $form->travel_from }} - {{ $form->travel_to }}</td></tr>
                    <tr><td class="lbl">Departure Date</td><td>{{ optional($form->departure_date)->format('d M Y') }}</td></tr>
                    @if ($form->trip_type === 'round_trip')
                        <tr><td class="lbl">Return Date</td><td>{{ optional($form->return_date)->format('d M Y') }}</td></tr>
                    @endif
                @else
                    <tr><td class="lbl">Check In Date</td><td>{{ optional($form->check_in_date)->format('d M Y') }}</td></tr>
                    <tr><td class="lbl">Check Out Date</td><td>{{ optional($form->check_out_date)->format('d M Y') }}</td></tr>
                @endif
            </table>

            <p>The documents related to this request are attached to this email.</p>

            <p>Best regards,<br>GMO Team</p>
        </div>
        <div class="footer">
            This email was sent automatically by the GMO Travel System — {{ config('company.name') }}, {{ config('company.plant') }}.
        </div>
    </div>
</body>
</html>
