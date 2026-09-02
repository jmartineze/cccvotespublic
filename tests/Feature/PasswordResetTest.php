<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    public function test_reset_link_is_sent_to_an_existing_user(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_does_not_leak_and_still_reports_success(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

            $response->assertRedirect(route('login'));
            $response->assertSessionHas('status');

            $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));

            return true;
        });
    }

    public function test_reset_fails_with_an_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_broker_config_points_at_the_expected_table(): void
    {
        $this->assertSame('password_reset_tokens', config('auth.passwords.users.table'));
        $this->assertNotNull(Password::broker());
    }
}
