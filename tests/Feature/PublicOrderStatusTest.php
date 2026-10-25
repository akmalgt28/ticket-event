<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_lookup_order_status_with_matching_email(): void
    {
        $order = Order::factory()->create([
            'customer_email' => 'buyer@example.com',
            'payment_status' => 'paid',
            'order_status' => 'paid',
        ]);
        Payment::factory()->for($order)->create([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->postJson('/api/orders/status', [
            'order_number' => $order->order_number,
            'customer_email' => 'BUYER@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.order_number', $order->order_number)
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.payment.status', 'paid')
            ->assertJsonMissingPath('data.customer_email');
    }

    public function test_guest_cannot_lookup_order_with_wrong_email(): void
    {
        $order = Order::factory()->create(['customer_email' => 'buyer@example.com']);

        $this->postJson('/api/orders/status', [
            'order_number' => $order->order_number,
            'customer_email' => 'other@example.com',
        ])->assertNotFound();
    }

    public function test_order_status_page_is_public(): void
    {
        $this->get('/order-status')->assertOk()->assertInertia(fn ($page) => $page->where('mode', 'status'));
    }

    public function test_payment_page_without_checkout_session_returns_to_status_lookup(): void
    {
        $this->get(route('public.orders.payment'))->assertRedirect(route('public.orders.status'));
    }

    public function test_payment_details_use_only_configured_bank_accounts(): void
    {
        config(['ticket-event.bank_transfer.accounts' => [
            ['bank_name' => 'Bank Pengujian', 'account_number' => '0012345678', 'account_holder' => 'Organizer Pengujian', 'private_note' => 'internal-only'],
            ['bank_name' => 'Incomplete'],
        ]]);
        $order = Order::factory()->create();

        $this->postJson('/api/orders/status', ['order_number' => $order->order_number, 'customer_email' => $order->customer_email])
            ->assertOk()
            ->assertJsonPath('data.payment_options.methods.0.id', 'bank_transfer')
            ->assertJsonCount(1, 'data.payment_options.bank_accounts')
            ->assertJsonPath('data.payment_options.bank_accounts.0.account_number', '0012345678')
            ->assertJsonMissingPath('data.payment_options.bank_accounts.0.private_note')
            ->assertJsonPath('data.event.slug', $order->event->slug);
    }

    public function test_unconfigured_bank_accounts_are_empty_instead_of_using_reference_data(): void
    {
        config(['ticket-event.bank_transfer.accounts' => []]);
        $order = Order::factory()->create();

        $this->postJson('/api/orders/status', ['order_number' => $order->order_number, 'customer_email' => $order->customer_email])
            ->assertOk()->assertJsonPath('data.payment_options.bank_accounts', []);
    }

    public function test_order_status_lookup_is_rate_limited(): void
    {
        $payload = [
            'order_number' => 'ORD-MISSING',
            'customer_email' => 'missing@example.com',
        ];

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson('/api/orders/status', $payload)->assertNotFound();
        }

        $this->postJson('/api/orders/status', $payload)->assertTooManyRequests();
    }

    public function test_lookup_matches_existing_mixed_case_email_and_returns_latest_payment(): void
    {
        $order = Order::factory()->create(['customer_email' => 'Buyer@Example.com']);
        Payment::factory()->for($order)->create(['status' => 'failed']);
        Payment::factory()->for($order)->create(['status' => 'pending', 'proof_uploaded_at' => now()]);

        $this->postJson('/api/orders/status', [
            'order_number' => $order->order_number, 'customer_email' => 'buyer@example.com',
        ])->assertOk()->assertJsonPath('data.payment.status', 'pending')
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonMissingPath('data.customer_email')
            ->assertJsonMissingPath('data.payment.proof_path');
    }

    public function test_status_lookup_expires_overdue_order_and_releases_quota_only_once(): void
    {
        $order = Order::factory()->create(['expires_at' => now()->subMinute()]);
        $ticket = TicketType::factory()->for($order->event)->create(['sold_count' => 3]);
        OrderItem::factory()->for($order)->for($ticket)->create(['quantity' => 2]);
        Payment::factory()->for($order)->create(['status' => 'pending']);
        $payload = ['order_number' => $order->order_number, 'customer_email' => $order->customer_email];

        $this->postJson('/api/orders/status', [...$payload, 'customer_email' => 'other@example.com'])->assertNotFound();
        $this->assertSame(3, $ticket->fresh()->sold_count);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->postJson('/api/orders/status', $payload)->assertOk()
                ->assertJsonPath('data.order_status', 'expired')
                ->assertJsonPath('data.payment_status', 'expired')
                ->assertJsonPath('data.payment.status', 'expired');
        }

        $this->assertSame(1, $ticket->fresh()->sold_count);
    }
}
