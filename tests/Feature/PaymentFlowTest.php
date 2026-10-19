<?php

namespace Tests\Feature;

use App\Mail\EticketMail;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_select_manual_transfer_for_pending_order(): void
    {
        $order = Order::factory()->create();

        $response = $this->postJson('/api/orders/'.$order->id.'/payments/manual', [
            'method' => 'bank_transfer',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.method', 'bank_transfer');
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'pending',
            'amount' => '150000.00',
        ]);
    }

    public function test_admin_can_verify_manual_payment_and_order_is_marked_paid(): void
    {
        Mail::fake();
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $order = Order::factory()->create();
        $this->postJson('/api/orders/'.$order->id.'/payments/manual', [
            'method' => 'bank_transfer',
        ])->assertCreated();

        $response = $this->actingAs($admin)->postJson('/payments/verify', [
            'order_id' => $order->id,
            'status' => 'paid',
            'provider_transaction_id' => 'TRX-123',
            'note' => 'Transfer verified',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'paid');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'paid',
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'paid',
            'provider_transaction_id' => 'TRX-123',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'payment.manual_verified',
        ]);
        Mail::assertQueued(EticketMail::class, function (EticketMail $mail) use ($order): bool {
            return $mail->orderNumber === $order->order_number;
        });
    }

    public function test_staff_cannot_verify_manual_payment(): void
    {
        $this->seed();
        $staff = User::factory()->create();
        $staff->assignRole('staff');
        $order = Order::factory()->create();

        $this->actingAs($staff)
            ->postJson('/payments/verify', [
                'order_id' => $order->id,
                'status' => 'paid',
            ])
            ->assertForbidden();
    }

    public function test_repeated_manual_payment_verification_is_idempotent(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $order = Order::factory()->create();
        OrderItem::factory()->for($order)->create(['quantity' => 1]);
        $this->postJson('/api/orders/'.$order->id.'/payments/manual', ['method' => 'bank_transfer'])->assertCreated();
        $payload = [
            'order_id' => $order->id,
            'status' => 'paid',
            'provider_transaction_id' => 'TRX-IDEMPOTENT',
        ];

        $this->actingAs($admin)->postJson('/payments/verify', $payload)->assertOk();
        $this->actingAs($admin)->postJson('/payments/verify', $payload)->assertOk();

        $this->assertSame(1, Ticket::query()->where('order_id', $order->id)->count());
        $this->assertSame(1, AuditLog::query()->where('action', 'payment.manual_verified')->count());
    }
}
