<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>E-ticket {{ $orderNumber }}</title>
    <style>
        @page { margin: 30px; }
        body { color: #101828; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.45; }
        .brand-bar { background: #123f86; color: #ffffff; padding: 18px 20px; }
        .brand { font-size: 22px; font-weight: bold; }
        .subtitle { color: #dce9ff; font-size: 11px; margin-top: 4px; }
        .section-title { color: #123f86; font-size: 13px; font-weight: bold; margin: 22px 0 8px; }
        .summary { border: 1px solid #d9e2f0; margin-bottom: 18px; width: 100%; }
        .summary td { padding: 8px 10px; }
        .label { color: #64748b; width: 24%; }
        .value { color: #101828; font-weight: bold; }
        .ticket { border: 1px solid #d9e2f0; margin-bottom: 16px; padding: 16px; page-break-inside: avoid; }
        .ticket-head { border-bottom: 1px solid #e7edf5; color: #123f86; font-size: 15px; font-weight: bold; padding-bottom: 10px; }
        .ticket-number { color: #101828; font-size: 11px; font-weight: bold; margin-top: 9px; }
        .qr { margin-top: 14px; text-align: center; }
        .qr img { height: 175px; width: 175px; }
        .hint { color: #64748b; font-size: 10px; text-align: center; }
        .footer { border-top: 1px solid #d9e2f0; color: #64748b; font-size: 10px; margin-top: 22px; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="brand-bar">
        <div class="brand">{{ config('app.name', 'Ticket Event') }}</div>
        <div class="subtitle">E-ticket pembayaran berhasil</div>
    </div>

    <div class="section-title">Ringkasan Pesanan</div>
    <table class="summary" cellpadding="0" cellspacing="0">
        <tr><td class="label">Nomor order</td><td class="value">{{ $orderNumber }}</td></tr>
        <tr><td class="label">Event</td><td class="value">{{ $eventTitle }}</td></tr>
        <tr><td class="label">Tanggal event</td><td>{{ $eventDetails['date'] }}</td></tr>
        <tr><td class="label">Jam event</td><td>{{ $eventDetails['time'] }}</td></tr>
        <tr><td class="label">Lokasi</td><td>{{ $eventDetails['location_name'] }}@if ($eventDetails['location_address'])<br>{{ $eventDetails['location_address'] }}@endif</td></tr>
        <tr><td class="label">Penyelenggara</td><td>{{ $eventDetails['organizer_name'] }}</td></tr>
    </table>

    <div class="section-title">Data Customer</div>
    <table class="summary" cellpadding="0" cellspacing="0">
        <tr><td class="label">Nama</td><td>{{ $customerName }}</td></tr>
        <tr><td class="label">Email</td><td>{{ $customerEmail }}</td></tr>
        <tr><td class="label">Nomor HP</td><td>{{ $customerPhone }}</td></tr>
    </table>

    <div class="section-title">Tiket</div>
    @foreach ($ticketData as $ticket)
        <div class="ticket">
            <div class="ticket-head">{{ $ticket['attendee_name'] }}</div>
            <div class="ticket-number">Nomor tiket: {{ $ticket['ticket_number'] }}</div>
            <table class="summary" cellpadding="0" cellspacing="0" style="margin-top: 12px; margin-bottom: 0;">
                <tr><td class="label">Jenis tiket</td><td>{{ $ticket['ticket_type_name'] }}</td></tr>
                <tr><td class="label">Harga</td><td>Rp {{ number_format((float) $ticket['ticket_price'], 0, ',', '.') }}</td></tr>
            </table>
            <div class="qr"><img src="{{ $ticket['qr_data_uri'] }}" alt="QR ticket {{ $ticket['ticket_number'] }}"></div>
            <div class="hint">Tunjukkan QR code ini saat check-in.</div>
        </div>
    @endforeach

    <div class="footer">Simpan PDF ini. Jangan bagikan QR code kepada orang lain.</div>
</body>
</html>