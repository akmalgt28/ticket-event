<?php

namespace App\Mail;

use App\Models\EmailDelivery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class EticketMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    /** @var array<int, array{ticket_number: string, attendee_name: string, qr_svg: string}> */
    public array $ticketData;

    public function __construct(
        public string $orderNumber,
        public string $eventTitle,
        Collection $tickets,
        public int $deliveryId,
    ) {
        $this->ticketData = $tickets->map(function ($ticket): array {
            return [
                'ticket_number' => $ticket->ticket_number,
                'attendee_name' => $ticket->attendee?->name ?? 'Peserta',
                'qr_svg' => (string) QrCode::format('svg')->size(220)->margin(1)->generate($ticket->qr_token),
            ];
        })->values()->all();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "E-ticket order {$this->orderNumber}",
            using: function ($message): void {
                $message->getHeaders()->addTextHeader(
                    'X-Ticket-Event-Delivery',
                    (string) $this->deliveryId,
                );
            },
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
        return [
            Attachment::fromData(
                fn (): string => Pdf::loadView('emails.eticket-pdf', [
                    'orderNumber' => $this->orderNumber,
                    'eventTitle' => $this->eventTitle,
                    'ticketData' => $this->ticketData,
                ])->setPaper('a4')->output(),
                "e-ticket-{$this->orderNumber}.pdf",
            )->withMime('application/pdf'),
        ];
    }

    public function failed(Throwable $exception): void
    {
        EmailDelivery::query()->whereKey($this->deliveryId)->update([
            'status' => 'failed',
            'last_error' => $exception->getMessage(),
            'failed_at' => now(),
        ]);
    }
}
