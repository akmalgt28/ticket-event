<?php

namespace Tests\Feature;

use App\Mail\EticketMail;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_diri_receives_selected_tickets_without_creating_an_order(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $ticket = TicketType::factory()->for($event)->create(['price' => '125000.00']);

        $url = route('public.events.data-diri', ['event' => $event->slug, 'qty' => [$ticket->id => 2]]);
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->get($url)->assertOk()->assertInertia(fn ($page) => $page
                ->component('Public/DataDiri')
                ->where('event.id', $event->id)
                ->where('event.ticket_types.0.price', '125000.00')
                ->where('items.0.ticket_type_id', $ticket->id)
                ->where('items.0.quantity', 2));
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(0, $ticket->fresh()->sold_count);
    }

    #[DataProvider('invalidQuantities')]
    public function test_invalid_selection_returns_to_checkout(mixed $quantity): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $ticket = TicketType::factory()->for($event)->create();

        $this->get(route('public.events.data-diri', ['event' => $event->slug, 'qty' => [$ticket->id => $quantity]]))
            ->assertRedirect(route('public.events.checkout', $event->slug))
            ->assertSessionHasErrors('qty');
        $this->assertDatabaseCount('orders', 0);
    }

    public static function invalidQuantities(): array
    {
        return [[0], [-1], [21], ['abc'], ['1.5'], [[1]]];
    }

    public function test_data_diri_requires_a_selection(): void
    {
        $event = Event::factory()->create(['status' => 'published']);

        $this->get(route('public.events.data-diri', $event->slug))
            ->assertRedirect(route('public.events.checkout', $event->slug))
            ->assertSessionHasErrors('qty');
    }

    public function test_event_detail_uses_event_configuration_without_placeholder_organizer_or_terms(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        config(['ticket-event.events' => [$event->slug => [
            'category' => 'Seminar', 'organizer_name' => 'Penyelenggara Resmi', 'terms' => 'Bawa identitas saat check-in.',
        ]]]);

        $this->get(route('public.events.show', $event->slug))->assertOk()->assertInertia(fn ($page) => $page
            ->where('event.category', 'Seminar')
            ->where('event.organizer_name', 'Penyelenggara Resmi')
            ->where('event.terms', 'Bawa identitas saat check-in.'));
    }

    public function test_returning_to_checkout_restores_selection_and_ignores_invalid_query(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $ticket = TicketType::factory()->for($event)->create();

        $this->get(route('public.events.checkout', ['event' => $event->slug, 'qty' => [$ticket->id => 2]]))
            ->assertOk()->assertInertia(fn ($page) => $page->where('quantities.'.$ticket->id, '2'));
        $this->get(route('public.events.checkout', ['event' => $event->slug, 'qty' => 'invalid']))
            ->assertOk()->assertInertia(fn ($page) => $page->where('quantities', []));
    }

    public function test_invalid_buyer_data_is_returned_with_errors_and_without_an_order(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $ticket = TicketType::factory()->for($event)->create();
        $payload = $this->payload($event, $ticket);
        $payload['customer_email'] = 'invalid';
        $url = route('public.events.data-diri', ['event' => $event->slug, 'qty' => [$ticket->id => 2]]);

        $this->from($url)->post(route('public.events.orders.store', $event->slug), $payload)
            ->assertRedirect($url)
            ->assertSessionHasErrors('customer_email')
            ->assertSessionHasInput('customer_name', 'Buyer')
            ->assertSessionHasInput('customer_phone', '08123456789');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(0, $ticket->fresh()->sold_count);
    }

    public function test_unavailable_and_foreign_tickets_are_rejected_on_data_diri(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $tickets = [
            TicketType::factory()->create(),
            TicketType::factory()->for($event)->create(['status' => 'inactive']),
            TicketType::factory()->for($event)->create(['sale_start_at' => now()->addDay()]),
            TicketType::factory()->for($event)->create(['sale_end_at' => now()->subDay()]),
            TicketType::factory()->for($event)->create(['quota' => 1, 'sold_count' => 1]),
        ];

        foreach ($tickets as $ticket) {
            $this->get(route('public.events.data-diri', ['event' => $event->slug, 'qty' => [$ticket->id => 1]]))
                ->assertRedirect(route('public.events.checkout', $event->slug))
                ->assertSessionHasErrors('qty');
        }
    }

    public function test_unpublished_purchase_pages_return_not_found(): void
    {
        $event = Event::factory()->create(['status' => 'draft']);

        $this->get(route('public.events.checkout', $event->slug))->assertNotFound();
        $this->get(route('public.events.data-diri', $event->slug))->assertNotFound();
    }

    public function test_guest_can_complete_purchase_through_payment_verification(): void
    {
        Mail::fake();
        Storage::fake('public');
        $this->seed();
        $event = Event::factory()->create(['status' => 'published']);
        $ticket = TicketType::factory()->for($event)->create(['price' => '125000.00', 'quota' => 5]);

        $this->get(route('public.events.checkout', $event->slug))->assertOk();
        $this->get(route('public.events.data-diri', ['event' => $event->slug, 'qty' => [$ticket->id => 2]]))->assertOk();
        $this->post(route('public.events.orders.store', $event->slug), $this->payload($event, $ticket))
            ->assertRedirect(route('public.orders.payment'));

        $order = Order::query()->sole();
        $this->assertSame('buyer@example.com', $order->customer_email);
        $this->assertSame('250000.00', $order->total_amount);
        $this->assertSame(2, $ticket->fresh()->sold_count);
        $this->assertSame(2, $order->attendees()->count());
        $this->assertSame('Designer', $order->attendees()->first()->metadata['profesi']);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'method' => 'bank_transfer', 'status' => 'pending']);

        $this->get(route('public.orders.payment'))->assertOk()->assertInertia(fn ($page) => $page
            ->component('Public/OrderStatus')
            ->where('mode', 'payment')
            ->where('checkout.order_number', $order->order_number));

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->get(route('public.orders.status'))->assertOk()->assertInertia(fn ($page) => $page
                ->component('Public/OrderStatus')
                ->where('checkout.order_number', $order->order_number)
                ->where('checkout.customer_email', 'buyer@example.com'));
        }
        $this->assertDatabaseCount('orders', 1);

        $lookup = ['order_number' => $order->order_number, 'customer_email' => 'BUYER@example.com'];
        $this->postJson('/api/orders/status', $lookup)->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.payment.status', 'pending');
        $this->post('/api/orders/'.$order->id.'/payments/manual/proof', [
            'customer_email' => 'buyer@example.com',
            'proof' => UploadedFile::fake()->image('receipt.png'),
        ])->assertOk();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->postJson('/payments/verify', ['order_id' => $order->id, 'status' => 'paid'])
            ->assertOk()->assertJsonPath('data.status', 'paid');

        $this->postJson('/api/orders/status', $lookup)->assertOk()
            ->assertJsonPath('data.order_status', 'paid')
            ->assertJsonPath('data.payment_status', 'paid');
        $this->assertSame(2, $order->tickets()->whereNotNull('attendee_id')->count());
        Mail::assertQueued(EticketMail::class, 1);
    }

    public function test_checkout_revalidates_quota_and_preserves_validation_errors(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $ticket = TicketType::factory()->for($event)->create(['quota' => 2]);
        $url = route('public.events.data-diri', ['event' => $event->slug, 'qty' => [$ticket->id => 2]]);
        $this->get($url)->assertOk();
        $ticket->update(['sold_count' => 1]);

        $this->from($url)->post(route('public.events.orders.store', $event->slug), $this->payload($event, $ticket))
            ->assertRedirect($url)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(1, $ticket->fresh()->sold_count);
    }

    public function test_payment_failure_rolls_back_the_entire_web_checkout(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $ticket = TicketType::factory()->for($event)->create();
        $this->mock(PaymentService::class, function ($mock): void {
            $mock->shouldReceive('selectManualTransfer')->once()
                ->andThrow(ValidationException::withMessages(['payment' => 'Payment unavailable.']));
        });

        $this->post(route('public.events.orders.store', $event->slug), $this->payload($event, $ticket))
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('attendees', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(0, $ticket->fresh()->sold_count);
    }

    public function test_checkout_rejects_mismatched_event_and_duplicate_ticket_rows(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $ticket = TicketType::factory()->for($event)->create();
        $payload = $this->payload($event, $ticket);
        $otherEvent = Event::factory()->create(['status' => 'published']);

        $this->post(route('public.events.orders.store', $otherEvent->slug), $payload)
            ->assertSessionHasErrors('event_id');

        $payload['items'][] = $payload['items'][0];
        $this->postJson('/api/orders', $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.ticket_type_id');
        $this->assertDatabaseCount('orders', 0);
    }

    private function payload(Event $event, TicketType $ticket): array
    {
        $attendee = ['name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '08123456789', 'metadata' => ['profesi' => 'Designer']];

        return [
            'event_id' => $event->id,
            'customer_name' => $attendee['name'],
            'customer_email' => 'BUYER@example.com',
            'customer_phone' => $attendee['phone'],
            'items' => [['ticket_type_id' => $ticket->id, 'quantity' => 2]],
            'attendees' => [$attendee, $attendee],
        ];
    }
}
