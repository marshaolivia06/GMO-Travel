<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Travel Booking Confirmation</title>
</head>
<body style="margin: 0; padding: 24px; font-family: Arial, sans-serif; color: #333;">
    <div style="max-width: 600px; margin: 0 auto;">
        <h2 style="margin-bottom: 8px;">TRAVEL BOOKING COMPLETED</h2>

        <p>Hello {{ $order->user->name ?? 'Employee' }},</p>

        <p>
            The GMO team has completed the travel booking process for your
            Travel Order. The following travel documents are now available:
        </p>

        <ul>
            @if ($order->ticket_arrangement === 'Booked by Company')
                <li>Final E-Ticket</li>
            @endif

            @if ($order->accommodation_arrangement === 'Booked by Company')
                <li>Final Hotel Reservation</li>
            @endif
        </ul>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">
                    Travel Order Number
                </td>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    {{ $order->order_number }}
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">
                    Department
                </td>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    {{ $order->department->name ?? '-' }}
                </td>
            </tr>
        </table>

        <p>
            Please log in to the Travel Management System to view your
            Travel Order details and access the available travel documents.
        </p>

        <p>Thank you.</p>
        <p>GMO Travel Management System</p>
    </div>
</body>
</html>
