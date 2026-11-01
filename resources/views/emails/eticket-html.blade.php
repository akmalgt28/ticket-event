<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-ticket {{ $orderNumber }}</title>
</head>
<body style="margin:0; padding:0; background:#eef3f9; color:#101828; font-family:Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3f9; padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%; max-width:600px; background:#ffffff;">
                <tr>
                    <td style="background:#123f86; padding:24px 28px; color:#ffffff;">
                        <div style="font-size:23px; line-height:1.2; font-weight:700;">{{ config('app.name', 'Ticket Event') }}</div>
                        <div style="margin-top:7px; color:#dce9ff; font-size:13px;">E-ticket pembayaran berhasil</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <h1 style="margin:0; color:#123f86; font-size:22px; line-height:1.25;">E-ticket kamu sudah siap</h1>
                        <p style="margin:12px 0 24px; color:#475467; font-size:14px; line-height:1.6;">Pembayaran untuk <strong style="color:#101828;">{{ $eventTitle }}</strong> sudah diverifikasi. Simpan email ini dan lampiran PDF untuk check-in.</p>

                        <h2 style="margin:0 0 10px; color:#123f86; font-size:16px;">Ringkasan Pesanan</h2>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d9e2f0; border-collapse:collapse; margin-bottom:24px;">
                            <tr><td style="padding:10px 12px; color:#64748b; width:35%;">Nomor order</td><td style="padding:10px 12px; color:#101828; font-weight:700;">{{ $orderNumber }}</td></tr>
                            <tr><td style="padding:10px 12px; color:#64748b;">Event</td><td style="padding:10px 12px; color:#101828; font-weight:700;">{{ $eventTitle }}</td></tr>
                        </table>

                        <h2 style="margin:0 0 10px; color:#123f86; font-size:16px;">Data Customer</h2>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-bottom:24px;">
                            <tr><td style="padding:6px 0; color:#64748b; width:35%;">Nama</td><td style="padding:6px 0; color:#101828;">{{ $customerName }}</td></tr>
                            <tr><td style="padding:6px 0; color:#64748b;">Email</td><td style="padding:6px 0; color:#101828;">{{ $customerEmail }}</td></tr>
                            <tr><td style="padding:6px 0; color:#64748b;">Nomor HP</td><td style="padding:6px 0; color:#101828;">{{ $customerPhone }}</td></tr>
                        </table>

                        <h2 style="margin:0 0 10px; color:#123f86; font-size:16px;">Tiket</h2>
                        @foreach ($ticketData as $ticket)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d9e2f0; border-collapse:collapse; margin-bottom:16px;">
                                <tr><td style="padding:13px 15px; background:#f4f7fb; color:#123f86; font-size:16px; font-weight:700;">{{ $ticket['attendee_name'] }}</td></tr>
                                <tr><td style="padding:15px; color:#101828;">Nomor tiket: <strong>{{ $ticket['ticket_number'] }}</strong><div style="color:#64748b; font-size:12px; text-align:center;">QR code tersedia di lampiran PDF untuk check-in.</div></td></tr>
                            </table>
                        @endforeach

                        <p style="margin:22px 0 0; color:#475467; font-size:13px; line-height:1.6;">PDF tiket terlampir pada email ini. Jangan bagikan QR code kepada orang lain.</p>
                    </td>
                </tr>
                <tr>
                    <td style="border-top:1px solid #d9e2f0; padding:16px 28px; color:#667085; font-size:12px;">Terima kasih,<br>{{ config('app.name', 'Ticket Event') }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>