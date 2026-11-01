<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerUserEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_an_admin_account(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $response = $this->actingAs($owner)->post(route('organizer.users.store'), [
            'name' => 'Admin Baru',
            'email' => 'admin.baru@example.com',
            'password' => 'password-kuat',
            'password_confirmation' => 'password-kuat',
            'role' => 'admin',
        ]);

        $response->assertRedirect();
        $user = User::query()->where('email', 'admin.baru@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotSame('password-kuat', $user->password);
    }

    public function test_admin_cannot_create_an_owner_account(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->from(route('organizer.users.index'))
            ->post(route('organizer.users.store'), [
                'name' => 'Owner Ilegal',
                'email' => 'owner.ilegal@example.com',
                'password' => 'password-kuat',
                'password_confirmation' => 'password-kuat',
                'role' => 'owner',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'owner.ilegal@example.com']);
    }

    public function test_staff_cannot_access_user_management(): void
    {
        $this->seed();
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff)->get(route('organizer.users.index'))->assertForbidden();
    }
}
