<?php

namespace Tests\Feature;

use App\Mail\EticketMail;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrganizerOrderEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_search_and_view_orders_without_raw_payment_data(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();
        $matchingOrder = Order::factory()->for($event)->create([
            'order_number' => 'ORD-MATCH',
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@example.com',
            'order_status' => 'paid',
            'payment_status' => 'paid',
        ]);
        $otherOrder = Order::factory()->for($event)->create([
            'order_number' => 'ORD-OTHER',
            'order_status' => 'pending',
            'payment_status' => 'pending',
        ]);
        Payment::factory()->for($matchingOrder)->create([
            'status' => 'paid',
            'raw_response' => ['secret' => 'do-not-expose'],
        ]);

        $response = $this->actingAs($admin)->getJson('/organizer/orders?status=paid&payment_status=paid&search=Budi');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'ORD-MATCH');

        $detail = $this->actingAs($admin)->getJson('/organizer/orders/'.$matchingOrder->id);

        $detail->assertOk()
            ->assertJsonPath('data.order_number', 'ORD-MATCH')
            ->assertJsonMissingPath('data.payments.0.raw_response');
        $this->assertModelExists($otherOrder);
    }

    public function test_staff_cannot_view_orders_or_participants(): void
    {
        $this->seed();
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff)->getJson('/organizer/orders')->assertForbidden();
        $this->actingAs($staff)->getJson('/organizer/participants')->assertForbidden();
    }

    public function test_admin_can_export_filtered_participants_as_csv(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $order = Order::factory()->create(['order_number' => 'ORD-CSV']);
        Attendee::factory()->for($order)->create([
            'name' => 'Dewi Lestari',
            'email' => 'dewi@example.com',
        ]);
        Attendee::factory()->create(['name' => 'Other Person']);

        $response = $this->actingAs($admin)->get('/organizer/participants/export?search=ORD-CSV');

        $response->assertDownload('participants.csv');
        $this->assertStringContainsString('"Dewi Lestari",dewi@example.com', $response->streamedContent());
        $this->assertStringNotContainsString('Other Person', $response->streamedContent());
    }

    public function test_admin_can_search_participants_by_name_email_or_order_number(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $order = Order::factory()->create(['order_number' => 'ORD-PARTICIPANT']);
        $attendee = Attendee::factory()->for($order)->create([
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
        ]);

        $response = $this->actingAs($admin)->getJson('/organizer/participants?search=ORD-PARTICIPANT');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $attendee->id)
            ->assertJsonPath('data.0.order.order_number', 'ORD-PARTICIPANT');
    }

    public function test_admin_payment_page_redirects_to_pending_orders(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('organizer.payments.index'))
            ->assertRedirect(route('organizer.orders.index', ['payment_status' => 'pending']));
    }

    public function test_admin_can_open_orders_inertia_page(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/organizer/orders');

        $response->assertOk()->assertInertia(fn ($page) => $page->component('Organizer/Orders'));
    }

    public function test_order_page_shows_latest_payment_and_its_transfer_proof(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $order = Order::factory()->create();
        Payment::factory()->for($order)->create(['status' => 'failed']);
        Payment::factory()->for($order)->create(['status' => 'pending', 'proof_path' => 'payment-proofs/latest.png']);

        $this->actingAs($admin)->get('/organizer/orders')->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('orders.data.0.payment.status', 'pending')
                ->where('orders.data.0.payment.proof_url', fn ($url) => str_ends_with($url, '/organizer/payments/'.$order->payments()->latest('id')->value('id').'/proof')));
    }

    public function test_admin_can_open_participants_inertia_page(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/organizer/participants');

        $response->assertOk()->assertInertia(fn ($page) => $page->component('Organizer/Participants'));
    }

    public function test_admin_can_resend_eticket_for_paid_order(): void
    {
        $this->seed();
        Mail::fake();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $order = Order::factory()->create(['order_status' => 'paid', 'payment_status' => 'paid']);
        Ticket::factory()->for($order)->create(['qr_token' => 'resend-token']);

        $this->actingAs($admin)
            ->post(route('organizer.orders.resend-eticket', $order))
            ->assertRedirect();

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

    public function test_staff_cannot_resend_eticket(): void
    {
        $this->seed();
        $staff = User::factory()->create();
        $staff->assignRole('staff');
        $order = Order::factory()->create(['order_status' => 'paid', 'payment_status' => 'paid']);
        Ticket::factory()->for($order)->create(['qr_token' => 'resend-token']);

        $this->actingAs($staff)
            ->post(route('organizer.orders.resend-eticket', $order))
            ->assertForbidden();
    }
}
