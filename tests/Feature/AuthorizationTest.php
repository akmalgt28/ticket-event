<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_roles_have_expected_permissions(): void
    {
        $this->seed();

        $owner = User::where('email', 'test@example.com')->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->assertTrue($owner->hasAllPermissions([
            'events.manage',
            'tickets.manage',
            'orders.manage',
            'payments.verify',
            'checkin.manage',
            'reports.view',
        ]));
        $this->assertTrue($admin->can('events.manage'));
        $this->assertTrue($admin->can('payments.verify'));
        $this->assertFalse($admin->can('checkin.manage'));
        $this->assertTrue($staff->can('checkin.manage'));
        $this->assertFalse($staff->can('orders.manage'));
    }

    public function test_sanctum_user_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();

        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_policies_enforce_role_permissions(): void
    {
        $this->seed();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $staff = User::factory()->create();
        $staff->assignRole('staff');
        $event = Event::factory()->create();
        $order = Order::factory()->for($event)->create();
        $payment = Payment::factory()->for($order)->create();
        $ticket = Ticket::factory()->for($order)->create();

        $this->assertTrue(Gate::forUser($admin)->allows('view', $event));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $order));
        $this->assertTrue(Gate::forUser($admin)->allows('verify', $payment));
        $this->assertFalse(Gate::forUser($staff)->allows('view', $order));
        $this->assertTrue(Gate::forUser($staff)->allows('checkIn', $ticket));
        $this->assertFalse(Gate::forUser($admin)->allows('checkIn', $ticket));
        $this->assertTrue(Gate::forUser($admin)->allows('viewReports'));
        $this->assertFalse(Gate::forUser($staff)->allows('viewReports'));
    }
}
