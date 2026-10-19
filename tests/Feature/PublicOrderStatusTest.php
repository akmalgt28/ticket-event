<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
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
        $this->get('/order-status')->assertOk();
    }
}
