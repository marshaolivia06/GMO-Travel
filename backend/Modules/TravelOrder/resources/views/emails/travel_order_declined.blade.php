<div style="max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #e2e5e9;border-radius:8px;overflow:hidden;">
    <div style="padding:20px 28px;border-bottom:1px solid #e5e7eb;">
        <div style="font-size:18px;font-weight:700;letter-spacing:.2px;">
            @if ($status === 'revision')
                TRAVEL ORDER REQUIRES REVISION
            @else
                TRAVEL ORDER REJECTED
            @endif
        </div>
    </div>

    <div style="padding:28px;">
        <p style="font-size:14px;line-height:1.6;color:#666;margin:0 0 8px;">Hello,</p>

        <p style="font-size:14px;line-height:1.7;margin:0 0 16px;">
            Travel Order <strong>{{ $order->order_number }}</strong> for
            <strong>{{ $order->user->name }}</strong>
            (Department <strong>{{ $order->department->name ?? '-' }}</strong>)
            @if ($status === 'revision')
                has been returned for <strong>revision</strong>
            @else
                has been <strong>rejected</strong>
            @endif
            by <strong>{{ $actorName }}</strong> ({{ $roleName }}).
        </p>

        <div style="border:1px solid #e2e5e9;border-radius:6px;padding:18px;margin-bottom:16px;">
            <div style="font-size:14px;font-weight:700;margin-bottom:10px;">Remarks</div>
            <div style="font-size:14px;line-height:1.7;color:#555;">{{ $remark }}</div>
        </div>

        <div style="border:1px solid #e2e5e9;border-radius:6px;padding:18px;margin-bottom:24px;">
            <div style="font-size:14px;font-weight:700;margin-bottom:16px;">Travel Details</div>

            <div style="margin-bottom:14px;">
                <div style="font-size:12px;color:#777;margin-bottom:4px;">Destination</div>
                <div style="font-size:14px;font-weight:600;">
                    {{ $order->travel_from }} → {{ $order->travel_to }}
                </div>
            </div>

            <div style="margin-bottom:14px;">
                <div style="font-size:12px;color:#777;margin-bottom:4px;">Departure</div>
                <div style="font-size:14px;font-weight:600;">
                    {{ \Carbon\Carbon::parse($order->departure_date)->format('d M Y') }},
                    {{ \Carbon\Carbon::parse($order->departure_time)->format('H:i') }}
                </div>
            </div>

            <div>
                <div style="font-size:12px;color:#777;margin-bottom:4px;">Return</div>
                <div style="font-size:14px;font-weight:600;">
                    {{ \Carbon\Carbon::parse($order->return_date)->format('d M Y') }},
                    {{ \Carbon\Carbon::parse($order->return_time)->format('H:i') }}
                </div>
            </div>
        </div>

        <p style="font-size:14px;line-height:1.7;margin:0 0 20px;">
            The Travel Order has been returned to draft. The requester can review and update the Travel Order before resubmitting it.
        </p>

        <p style="margin:0;">
            <a href="{{ config('app.frontend_url') }}" style="display:inline-block;padding:11px 20px;background:#333333;color:#ffffff;text-decoration:none;border-radius:6px;font-size:14px;font-weight:600;">
                Open Application
            </a>
        </p>
    </div>

    <div style="padding:16px 28px;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.5;color:#888;">
        This email was sent automatically by GMO Travel. Please do not reply to this email.
    </div>
</div>