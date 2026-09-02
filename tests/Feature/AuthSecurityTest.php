<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        RateLimiter::clear('super@ccc.local|127.0.0.1');
        parent::tearDown();
    }

    public function test_login_locks_out_after_five_failed_attempts(): void
    {
        User::factory()->create(['email' => 'super@ccc.local', 'role' => 'super_admin']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['identifier' => 'super@ccc.local', 'password' => 'wrong']);
        }

        // 6th attempt — even with the CORRECT password — is refused by the limiter
        $response = $this->post('/login', ['identifier' => 'super@ccc.local', 'password' => 'password']);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
        $this->assertStringContainsString(
            'Too many login attempts',
            session('errors')->first('identifier')
        );
    }

    public function test_successful_login_clears_the_rate_limiter(): void
    {
        User::factory()->create(['email' => 'super@ccc.local', 'role' => 'super_admin']);

        $this->post('/login', ['identifier' => 'super@ccc.local', 'password' => 'wrong']);
        $this->post('/login', ['identifier' => 'super@ccc.local', 'password' => 'wrong']);

        $this->post('/login', ['identifier' => 'super@ccc.local', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(0, RateLimiter::attempts('super@ccc.local|127.0.0.1'));
    }

    public function test_robots_txt_disallows_everything(): void
    {
        // Served statically by the web server, not routed through Laravel.
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Disallow: /', $robots);
        $this->assertStringNotContainsString("Disallow:\n", $robots);
    }

    public function test_authenticated_pages_are_marked_noindex(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant_admin']);

        $this->actingAs($tenant)->get('/')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_protected_pages_redirect_guests_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/results')->assertRedirect(route('login'));
        $this->get('/admin/contests')->assertRedirect(route('login'));
        $this->get('/super-admin/tenants')->assertRedirect(route('login'));
    }

    public function test_judge_cannot_reach_admin_or_super_admin_areas(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant_admin']);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $tenant->id, 'email' => null, 'username' => 'j']);

        $this->actingAs($judge)->get('/admin/contests')->assertForbidden();
        $this->actingAs($judge)->get('/admin/users')->assertForbidden();
        $this->actingAs($judge)->get('/super-admin/tenants')->assertForbidden();
    }

    public function test_tenant_admin_cannot_reach_super_admin_area(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant_admin']);

        $this->actingAs($tenant)->get('/super-admin/tenants')->assertForbidden();
    }
}
