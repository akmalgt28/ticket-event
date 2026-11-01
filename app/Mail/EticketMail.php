<?php

namespace App\Mail;

use App\Models\EmailDelivery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class EticketMail extends Mailable
{
    use SerializesModels;

    /** @var array<int, array{ticket_number: string, attendee_name: string, ticket_type_name: string, ticket_price: string, qr_svg: string, qr_data_uri: string}> */
    public array $ticketData;

    /** @var array{date: string, time: string, location_name: string, location_address: ?string, organizer_name: string} */
    public array $eventDetails;

    public function __construct(
        public string $orderNumber,
        public string $eventTitle,
        public string $customerName,
        public string $customerEmail,
        public string $customerPhone,
        Collection $tickets,
        public int $deliveryId,
        array $eventDetails = [],
    ) {
        $this->mailer('resend');
        $this->eventDetails = array_merge([
            'date' => '-',
            'time' => '-',
            'location_name' => '-',
            'location_address' => null,
            'organizer_name' => 'Penyelenggara Event',
        ], $eventDetails);
        $this->ticketData = $tickets->map(function ($ticket): array {
            $qrSvg = (string) QrCode::format('svg')->size(220)->margin(1)->generate($ticket->qr_token);

            return [
                'ticket_number' => $ticket->ticket_number,
                'attendee_name' => $ticket->attendee?->name ?? 'Peserta',
                'ticket_type_name' => $ticket->orderItem?->ticket_name_snapshot ?? 'Tiket',
                'ticket_price' => (string) ($ticket->orderItem?->unit_price ?? '0'),
                'qr_svg' => $qrSvg,
                'qr_data_uri' => 'data:image/svg+xml;base64,'.base64_encode($qrSvg),
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
            view: 'emails.eticket-html',
            text: 'emails.eticket-text',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => Pdf::loadView('emails.eticket-pdf', [
                    'orderNumber' => $this->orderNumber,
                    'eventTitle' => $this->eventTitle,
                    'customerName' => $this->customerName,
                    'customerEmail' => $this->customerEmail,
                    'customerPhone' => $this->customerPhone,
                    'ticketData' => $this->ticketData,
                    'eventDetails' => $this->eventDetails,
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
