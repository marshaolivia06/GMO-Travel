<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>E-Ticket Available</title>
</head>
<body style="margin: 0; padding: 24px; font-family: Arial, sans-serif; color: #333;">
    <div style="max-width: 600px; margin: 0 auto;">
        <h2 style="margin-bottom: 8px;">TRAVEL ORDER E-TICKET AVAILABLE</h2>

        <p>Hello {{ $order->user->name ?? 'Employee' }},</p>

        <p>
            The GMO team has completed the travel booking process.
            Your final E-Ticket is now available.
        </p>

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
            Travel Order details and access the E-Ticket.
        </p>

        <p>Thank you.</p>
        <p>GMO Travel Management System</p>
    </div>
</body>
</html>