<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>E-ticket {{ $orderNumber }}</title>
    <style>
        @page { margin: 28px; }
        body { color: #101014; font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { border-bottom: 3px solid #144ba3; margin-bottom: 22px; padding-bottom: 14px; }
        .brand { color: #144ba3; font-size: 22px; font-weight: bold; }
        .muted { color: #64748b; }
        .ticket { border: 1px solid #dbe3ef; margin-bottom: 18px; padding: 18px; }
        .ticket-title { font-size: 16px; font-weight: bold; margin: 0 0 8px; }
        .ticket-number { color: #144ba3; font-size: 13px; font-weight: bold; }
        .qr { margin-top: 18px; text-align: center; }
        .qr svg { height: 180px; width: 180px; }
        .footer { border-top: 1px solid #dbe3ef; color: #64748b; margin-top: 24px; padding-top: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">{{ config('app.name', 'Ticket Event') }}</div>
        <div class="muted">E-ticket pembayaran berhasil</div>
    </div>

    <p><strong>Order:</strong> {{ $orderNumber }}</p>
    <p><strong>Event:</strong> {{ $eventTitle }}</p>

    @foreach ($ticketData as $ticket)
        <div class="ticket">
            <p class="ticket-title">{{ $ticket['attendee_name'] }}</p>
            <div class="ticket-number">{{ $ticket['ticket_number'] }}</div>
            <div class="qr">{!! $ticket['qr_svg'] !!}</div>
            <p class="muted">Tunjukkan QR code ini saat check-in.</p>
        </div>
    @endforeach

    <div class="footer">Simpan PDF ini dan jangan bagikan QR code kepada orang lain.</div>
</body>
</html>
