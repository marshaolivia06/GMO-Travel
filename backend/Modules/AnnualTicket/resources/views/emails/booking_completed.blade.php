<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Annual Ticket Booking Completed</title>
</head>
<body style="margin: 0; padding: 24px; font-family: Arial, sans-serif; color: #333;">
    <div style="max-width: 600px; margin: 0 auto;">
        <h2 style="margin-bottom: 8px;">ANNUAL TICKET BOOKING COMPLETED</h2>

        <p>Hello {{ $ticket->employee->name ?? 'Employee' }},</p>

        <p>
            The GMO team has completed the ticket booking process for your
            Annual Ticket. The E-Ticket / Travel Document is now available:
        </p>

        <ul>
            <li>E-Ticket / Travel Document</li>
        </ul>

        @isset($emailMessage)
            <div style="border-left: 4px solid #ddd; margin: 20px 0; padding: 10px 16px; white-space: pre-wrap;">
                {!! nl2br(e($emailMessage)) !!}
            </div>
        @endisset

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">
                    Annual Ticket Number
                </td>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    {{ $ticket->document_number }}
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">
                    Travel Period
                </td>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    {{ optional($ticket->legs->min('travel_date'))->format('d M Y') }} - {{ optional($ticket->legs->max('travel_date'))->format('d M Y') }}
                </td>
            </tr>
        </table>

        <p>
            Please log in to the Travel Management System to view your
            Annual Ticket details and access the available travel documents.
        </p>

        <p>Thank you.</p>
        <p>GMO Travel Management System</p>
    </div>
</body>
</html>
