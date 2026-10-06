<p>Halo,</p>

<p>
    Travel Order <strong>{{ $order->order_number }}</strong> dari
    <strong>{{ $order->user->name }}</strong> menunggu approval Anda
    sebagai <strong>{{ $stageName }}</strong>.
</p>

<ul>
    <li>Tujuan: {{ $order->travel_from }} → {{ $order->travel_to }}</li>
    <li>Berangkat: {{ \Carbon\Carbon::parse($order->departure_date)->format('d M Y') }}, {{ \Carbon\Carbon::parse($order->departure_time)->format('H:i') }}</li>
    <li>Kembali: {{ \Carbon\Carbon::parse($order->return_date)->format('d M Y') }}, {{ \Carbon\Carbon::parse($order->return_time)->format('H:i') }}</li>
</ul>

<p><a href="{{ config('app.frontend_url') }}">Buka aplikasi</a></p>