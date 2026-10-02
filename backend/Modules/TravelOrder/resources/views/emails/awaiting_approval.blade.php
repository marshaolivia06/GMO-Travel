<p>Halo,</p>

<p>
    Travel Order <strong>{{ $order->order_number }}</strong> dari
    <strong>{{ $order->user->name }}</strong> menunggu approval Anda
    sebagai <strong>{{ $stageName }}</strong>.
</p>

<ul>
    <li>Tujuan: {{ $order->travel_from }} → {{ $order->travel_to }}</li>
    <li>Berangkat: {{ $order->departure_date }}</li>
    <li>Kembali: {{ $order->return_date }}</li>
</ul>

<p><a href="{{ config('app.frontend_url') }}">Buka aplikasi</a></p>