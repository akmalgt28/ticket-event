<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_expires_order_releases_quota_and_expires_payment(): void
    {
        $this->travelTo('2026-10-19 12:00:00');
        $order = Order::factory()->create(['expires_at' => now()->subMinute()]);
        $ticketType = TicketType::factory()->for($order->event)->create(['sold_count' => 3]);
        OrderItem::factory()->for($order)->for($ticketType)->create(['quantity' => 2]);
        Payment::factory()->for($order)->create();

        $this->artisan('orders:expire')
            ->expectsOutput('Expired 1 order(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'expired',
            'payment_status' => 'expired',
        ]);
        $this->assertDatabaseHas('ticket_types', ['id' => $ticketType->id, 'sold_count' => 1]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'expired']);
    }

    public function test_command_does_not_expire_active_order(): void
    {
        $this->travelTo('2026-10-19 12:00:00');
        $order = Order::factory()->create(['expires_at' => now()->addMinute()]);

        $this->artisan('orders:expire')
            ->expectsOutput('Expired 0 order(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }
}
