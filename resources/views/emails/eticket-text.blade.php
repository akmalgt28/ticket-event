E-TICKET KAMU SUDAH SIAP

Pembayaran untuk {{ $eventTitle }} sudah diverifikasi.

RINGKASAN PESANAN
Nomor order: {{ $orderNumber }}
Event: {{ $eventTitle }}

DATA CUSTOMER
Nama: {{ $customerName }}
Email: {{ $customerEmail }}
Nomor HP: {{ $customerPhone }}

TIKET
@foreach ($ticketData as $ticket)
- {{ $ticket['attendee_name'] }}
  Nomor tiket: {{ $ticket['ticket_number'] }}
@endforeach

PDF tiket terlampir. Tunjukkan QR code dari PDF saat check-in.
Jangan bagikan QR code kepada orang lain.

Terima kasih,
{{ config('app.name', 'Ticket Event') }}