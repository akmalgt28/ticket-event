<?php

namespace Tests\Feature;

use App\Mail\EticketMail;
use App\Models\EmailDelivery;
use App\Models\Order;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SentMessage as LaravelSentMessage;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Symfony\Component\Mailer\Envelope as SymfonyEnvelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class EmailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_mail_callback_marks_delivery_as_failed(): void
    {
        $order = Order::factory()->create();
        $delivery = EmailDelivery::query()->create([
            'order_id' => $order->id,
            'recipient' => $order->customer_email,
            'type' => 'eticket',
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        (new EticketMail($order->order_number, 'Event', collect(), $delivery->id))
            ->failed(new RuntimeException('SMTP unavailable'));

        $this->assertDatabaseHas('email_deliveries', [
            'id' => $delivery->id,
            'status' => 'failed',
            'last_error' => 'SMTP unavailable',
        ]);
    }

    public function test_message_sent_event_marks_delivery_as_sent(): void
    {
        $order = Order::factory()->create();
        $delivery = EmailDelivery::query()->create([
            'order_id' => $order->id,
            'recipient' => $order->customer_email,
            'type' => 'eticket',
            'status' => 'queued',
            'queued_at' => now(),
        ]);
        $email = (new Email)
            ->from('sender@example.com')
            ->to($order->customer_email)
            ->subject('E-ticket')
            ->text('Ticket');
        $email->getHeaders()->addTextHeader('X-Ticket-Event-Delivery', (string) $delivery->id);
        $sentMessage = new SymfonySentMessage(
            $email,
            new SymfonyEnvelope(new Address('sender@example.com'), [new Address($order->customer_email)]),
        );
        $sentMessage->setMessageId('<message-123@example.com>');

        Event::dispatch(new MessageSent(new LaravelSentMessage($sentMessage)));

        $this->assertDatabaseHas('email_deliveries', [
            'id' => $delivery->id,
            'status' => 'sent',
            'message_id' => '<message-123@example.com>',
        ]);
    }

    public function test_eticket_mail_contains_pdf_attachment(): void
    {
        $order = Order::factory()->create();
        $delivery = EmailDelivery::query()->create([
            'order_id' => $order->id,
            'recipient' => $order->customer_email,
            'type' => 'eticket',
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        $mail = new EticketMail($order->order_number, 'Event', collect(), $delivery->id);
        $attachments = $mail->attachments();
        $pdf = null;
        $attachments[0]->attachWith(
            fn (string $path): null => null,
            function (Closure $data) use (&$pdf): null {
                $pdf = $data();

                return null;
            },
        );

        $this->assertCount(1, $attachments);
        $this->assertIsString($pdf);
        $this->assertStringStartsWith('%PDF-', $pdf);
    }
}
