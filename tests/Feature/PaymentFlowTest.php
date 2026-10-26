<?php

namespace Tests\Feature;

use App\Mail\EticketMail;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_select_manual_transfer_for_pending_order(): void
    {
        $order = Order::factory()->create();

        $response = $this->postJson('/api/orders/'.$order->id.'/payments/manual', [
            'method' => 'bank_transfer',
            'customer_email' => $order->customer_email,
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

    public function test_guest_cannot_select_manual_payment_for_another_customers_order(): void
    {
        $order = Order::factory()->create(['customer_email' => 'owner@example.com']);

        $this->postJson('/api/orders/'.$order->id.'/payments/manual', [
            'method' => 'bank_transfer',
            'customer_email' => 'other@example.com',
        ])->assertForbidden();
    }

    public function test_customer_can_upload_manual_payment_proof(): void
    {
        Storage::fake('local');
        $order = Order::factory()->create();

        $this->postJson('/api/orders/'.$order->id.'/payments/manual', [
            'method' => 'bank_transfer',
            'customer_email' => $order->customer_email,
        ])->assertCreated();

        $response = $this->post('/api/orders/'.$order->id.'/payments/manual/proof', [
            'customer_email' => $order->customer_email,
            'proof' => UploadedFile::fake()->image('receipt.jpg'),
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.proof_url', null);
        $payment = $order->payments()->firstOrFail();
        $this->assertNotNull($payment->proof_path);
        Storage::disk('local')->assertExists($payment->proof_path);
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
            'customer_email' => $order->customer_email,
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
        $this->assertDatabaseHas('email_deliveries', [
            'order_id' => $order->id,
            'recipient' => $order->customer_email,
            'type' => 'eticket',
            'status' => 'queued',
        ]);
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
        $this->postJson('/api/orders/'.$order->id.'/payments/manual', ['method' => 'bank_transfer', 'customer_email' => $order->customer_email])->assertCreated();
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

    public function test_manual_payment_selection_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson('/api/orders/999999/payments/manual', [
                'method' => 'bank_transfer',
            ])->assertNotFound();
        }

        $this->postJson('/api/orders/999999/payments/manual', [
            'method' => 'bank_transfer',
        ])->assertTooManyRequests();
    }

    public function test_selecting_manual_transfer_twice_reuses_the_pending_payment(): void
    {
        $order = Order::factory()->create();
        $payload = ['method' => 'bank_transfer', 'customer_email' => $order->customer_email];
        $first = $this->postJson('/api/orders/'.$order->id.'/payments/manual', $payload)->assertCreated();
        $second = $this->postJson('/api/orders/'.$order->id.'/payments/manual', $payload)->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_retrying_failed_payment_resets_order_payment_status(): void
    {
        $order = Order::factory()->create(['payment_status' => 'failed']);
        Payment::factory()->for($order)->create(['status' => 'failed']);

        $this->postJson('/api/orders/'.$order->id.'/payments/manual', [
            'method' => 'bank_transfer', 'customer_email' => $order->customer_email,
        ])->assertCreated()->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'pending']);
        $this->assertSame(2, $order->payments()->count());
    }

    public function test_pending_payment_cannot_be_verified_after_deadline_or_cancellation(): void
    {
        Mail::fake();
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        foreach ([['expires_at' => now()->subMinute()], ['order_status' => 'cancelled']] as $attributes) {
            $order = Order::factory()->create($attributes);
            Payment::factory()->for($order)->create(['status' => 'pending']);

            $this->postJson('/payments/verify', ['order_id' => $order->id, 'status' => 'paid'])
                ->assertUnprocessable()->assertJsonValidationErrors('order_id');
            $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'pending']);
            $this->assertNull($order->fresh()->paid_at);
        }

        $this->assertDatabaseCount('tickets', 0);
        Mail::assertNothingQueued();
    }

    public function test_proof_upload_requires_matching_customer_and_valid_file(): void
    {
        Storage::fake('local');
        $order = Order::factory()->create();
        Payment::factory()->for($order)->create(['status' => 'pending']);
        $url = '/api/orders/'.$order->id.'/payments/manual/proof';

        $this->postJson($url, ['customer_email' => 'other@example.com', 'proof' => UploadedFile::fake()->image('proof.png')])
            ->assertForbidden();
        $this->postJson($url, ['customer_email' => $order->customer_email, 'proof' => UploadedFile::fake()->create('proof.txt', 1, 'text/plain')])
            ->assertUnprocessable()->assertJsonValidationErrors('proof');
        $this->postJson($url, ['customer_email' => $order->customer_email, 'proof' => UploadedFile::fake()->create('proof.pdf', 5121, 'application/pdf')])
            ->assertUnprocessable()->assertJsonValidationErrors('proof');

        $this->assertNull($order->payments()->first()->proof_path);
        $this->assertSame([], Storage::disk('local')->allFiles('payment-proofs'));
    }
}
