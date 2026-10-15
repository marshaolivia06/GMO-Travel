@php
    $val = fn ($value) => ($value === null || $value === '') ? '-' : $value;
    $date = fn ($value) => $value
        ? \Carbon\Carbon::parse($value)->format('d M Y')
        : '-';
    $time = fn ($value) => $value
        ? \Carbon\Carbon::parse($value)->format('H:i')
        : '-';

    $booking = $order->booking;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Order Booking Submitted</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#263238;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0"
                    style="width:100%;max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;">

                    <tr>
                        <td style="background:#17365d;padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:12px;letter-spacing:1px;">
                                TRAVEL MANAGEMENT
                            </p>
                            <h1 style="margin:0;font-size:22px;font-weight:600;">
                                BOOKING INFORMATION SUBMITTED
                            </h1>
                            <p style="margin:10px 0 0;font-size:14px;color:#e5edf7;">
                                Travel Order {{ $val($order->order_number) }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 32px;">
                            <p style="margin:0 0 16px;font-size:15px;">
                                Dear Finance &amp; Accounting Team,
                            </p>

                            <p style="margin:0 0 24px;font-size:14px;line-height:1.7;color:#52606d;">
                                The GMO team has submitted the booking information for the following
                                Travel Order. Please review the details for further processing.
                            </p>

                            <h2 style="margin:0 0 12px;font-size:16px;color:#17365d;">
                                Travel Order Details
                            </h2>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                style="border-collapse:collapse;font-size:14px;">
                                <tr>
                                    <td style="padding:11px 12px;background:#f4f6f8;width:38%;border-bottom:1px solid #e5e7eb;">
                                        Travel Order No.
                                    </td>
                                    <td style="padding:11px 12px;border-bottom:1px solid #e5e7eb;">
                                        {{ $val($order->order_number) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:11px 12px;background:#f4f6f8;border-bottom:1px solid #e5e7eb;">
                                        Requester
                                    </td>
                                    <td style="padding:11px 12px;border-bottom:1px solid #e5e7eb;">
                                        {{ $val($order->user?->name) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:11px 12px;background:#f4f6f8;border-bottom:1px solid #e5e7eb;">
                                        Department
                                    </td>
                                    <td style="padding:11px 12px;border-bottom:1px solid #e5e7eb;">
                                        {{ $val($order->department?->department_name) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:11px 12px;background:#f4f6f8;border-bottom:1px solid #e5e7eb;">
                                        Destination
                                    </td>
                                    <td style="padding:11px 12px;border-bottom:1px solid #e5e7eb;">
                                        {{ $val($order->destination ?? $order->to ?? null) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:11px 12px;background:#f4f6f8;border-bottom:1px solid #e5e7eb;">
                                        Departure
                                    </td>
                                    <td style="padding:11px 12px;border-bottom:1px solid #e5e7eb;">
                                        {{ $date($booking?->departure_date) }}
                                        {{ $time($booking?->departure_time) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:11px 12px;background:#f4f6f8;border-bottom:1px solid #e5e7eb;">
                                        Return
                                    </td>
                                    <td style="padding:11px 12px;border-bottom:1px solid #e5e7eb;">
                                        {{ $date($booking?->return_date) }}
                                        {{ $time($booking?->return_time) }}
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0;font-size:14px;line-height:1.7;color:#52606d;">
                                This is an automated notification from the Travel Management System.
                                Please do not reply to this email.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 32px;background:#f4f6f8;text-align:center;font-size:12px;color:#77838f;">
                            © {{ date('Y') }} Travel Management
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>