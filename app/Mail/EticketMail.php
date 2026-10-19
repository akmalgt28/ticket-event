<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EticketMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var array<int, array{ticket_number: string, attendee_name: string, qr_svg: string}> */
    public array $ticketData;

    public function __construct(
        public string $orderNumber,
        public string $eventTitle,
        Collection $tickets,
        array $qrTokens,
    ) {
        $this->ticketData = $tickets->map(function ($ticket) use ($qrTokens): array {
            return [
                'ticket_number' => $ticket->ticket_number,
                'attendee_name' => $ticket->attendee?->name ?? 'Peserta',
                'qr_svg' => QrCode::format('svg')->size(220)->margin(1)->generate($qrTokens[$ticket->id]),
            ];
        })->values()->all();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "E-ticket order {$this->orderNumber}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.eticket',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
