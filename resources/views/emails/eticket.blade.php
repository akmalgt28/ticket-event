<x-mail::message>
# E-ticket kamu sudah siap

Pembayaran untuk **{{ $eventTitle }}** sudah diverifikasi. Simpan email ini dan lampiran PDF untuk check-in.

<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin:24px 0; border:1px solid #d9e2f0;">
    <tr>
        <td colspan="2" style="padding:14px 16px; background:#123f86; color:#ffffff; font-size:16px; font-weight:700;">
            Ringkasan Pesanan
        </td>
    </tr>
    <tr>
        <td style="padding:10px 16px; color:#64748b; width:38%;">Nomor order</td>
        <td style="padding:10px 16px; color:#101828; font-weight:700;">{{ $orderNumber }}</td>
    </tr>
    <tr>
        <td style="padding:10px 16px; color:#64748b;">Event</td>
        <td style="padding:10px 16px; color:#101828; font-weight:700;">{{ $eventTitle }}</td>
    </tr>
</table>

## Data Customer

<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin:12px 0 24px;">
    <tr>
        <td style="padding:6px 0; color:#64748b; width:38%;">Nama</td>
        <td style="padding:6px 0; color:#101828;">{{ $customerName }}</td>
    </tr>
    <tr>
        <td style="padding:6px 0; color:#64748b;">Email</td>
        <td style="padding:6px 0; color:#101828;">{{ $customerEmail }}</td>
    </tr>
    <tr>
        <td style="padding:6px 0; color:#64748b;">Nomor HP</td>
        <td style="padding:6px 0; color:#101828;">{{ $customerPhone }}</td>
    </tr>
</table>

## Tiket

@foreach ($ticketData as $ticket)
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin:0 0 20px; border:1px solid #d9e2f0;">
    <tr>
        <td style="padding:14px 16px; background:#f4f7fb; color:#123f86; font-size:16px; font-weight:700;">
            {{ $ticket['attendee_name'] }}
        </td>
    </tr>
    <tr>
        <td style="padding:14px 16px; color:#101828;">
            Nomor tiket: <strong>{{ $ticket['ticket_number'] }}</strong>
            <div style="text-align:center; color:#64748b; font-size:12px;">QR code tersedia di lampiran PDF untuk check-in.</div>
        </td>
    </tr>
</table>
@endforeach

PDF tiket sudah dilampirkan pada email ini.

Terima kasih,<br>
{{ config('app.name', 'Ticket Event') }}
</x-mail::message>