<x-mail::message>
# E-ticket kamu siap

Order **{{ $orderNumber }}** untuk event **{{ $eventTitle }}** sudah dibayar.

@foreach ($ticketData as $ticket)
## {{ $ticket['attendee_name'] }}

Nomor tiket: **{{ $ticket['ticket_number'] }}**

<div style="text-align: center; margin: 20px 0;">
    {!! $ticket['qr_svg'] !!}
</div>
@endforeach

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
