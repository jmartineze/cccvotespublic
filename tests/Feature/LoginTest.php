<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function judgeWithBoth(): User
    {
        $tenant = $this->makeTenantAdmin();

        return User::factory()->create([
            'role' => 'judge',
            'owner_id' => $tenant->id,
            'username' => 'nova',
            'email' => 'nova@example.com',
            'password' => Hash::make('the-password-1'),
        ]);
    }

    public function test_a_user_with_both_can_sign_in_with_their_username(): void
    {
        $user = $this->judgeWithBoth();

        $this->post('/login', ['identifier' => 'nova', 'password' => 'the-password-1'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_user_with_both_can_sign_in_with_their_email(): void
    {
        $user = $this->judgeWithBoth();

        $this->post('/login', ['identifier' => 'nova@example.com', 'password' => 'the-password-1'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_judge_with_only_a_username_still_signs_in(): void
    {
        $tenant = $this->makeTenantAdmin();
        $user = User::factory()->create([
            'role' => 'judge', 'owner_id' => $tenant->id, 'email' => null,
            'username' => 'solo', 'password' => Hash::make('the-password-1'),
        ]);

        $this->post('/login', ['identifier' => 'solo', 'password' => 'the-password-1'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_tenant_admin_signs_in_with_email(): void
    {
        $admin = User::factory()->create([
            'role' => 'tenant_admin', 'email' => 'ta@example.com', 'username' => null,
            'password' => Hash::make('the-password-1'),
        ]);

        $this->post('/login', ['identifier' => 'ta@example.com', 'password' => 'the-password-1'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_wrong_password_is_rejected_for_either_identifier(): void
    {
        $this->judgeWithBoth();

        $this->post('/login', ['identifier' => 'nova', 'password' => 'nope'])
            ->assertSessionHasErrors('identifier');
        $this->assertGuest();

        $this->post('/login', ['identifier' => 'nova@example.com', 'password' => 'nope'])
            ->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_unknown_identifier_is_rejected(): void
    {
        $this->post('/login', ['identifier' => 'ghost', 'password' => 'whatever'])
            ->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }
}
