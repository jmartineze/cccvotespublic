<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_judge_updates_name_username_and_email(): void
    {
        $tenant = $this->makeTenantAdmin();
        $judge = $this->makeJudge($tenant, 'oldname');

        $this->actingAs($judge)->post('/profile', [
            'name' => 'New Name',
            'username' => 'newname',
            'email' => 'judge@example.com',
        ])->assertRedirect(route('profile.edit'))->assertSessionHas('success');

        $judge->refresh();
        $this->assertSame('New Name', $judge->name);
        $this->assertSame('newname', $judge->username);
        $this->assertSame('judge@example.com', $judge->email);
    }

    public function test_judge_email_is_optional(): void
    {
        $tenant = $this->makeTenantAdmin();
        $judge = $this->makeJudge($tenant, 'nova');
        $judge->update(['email' => 'x@example.com']);

        $this->actingAs($judge)->post('/profile', [
            'name' => $judge->name,
            'username' => 'nova',
            'email' => '',
        ])->assertRedirect(route('profile.edit'));

        $this->assertNull($judge->fresh()->email);
    }

    public function test_judge_cannot_clear_their_username(): void
    {
        $tenant = $this->makeTenantAdmin();
        $judge = $this->makeJudge($tenant, 'nova');

        $this->actingAs($judge)->post('/profile', [
            'name' => $judge->name,
            'username' => '',
        ])->assertSessionHasErrors('username');
    }

    public function test_tenant_admin_email_is_required_and_username_optional(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant_admin', 'email' => 'ta@example.com', 'username' => null]);

        $this->actingAs($tenant)->post('/profile', [
            'name' => 'Boss',
            'username' => '',
            'email' => '',
        ])->assertSessionHasErrors('email');

        $this->actingAs($tenant)->post('/profile', [
            'name' => 'Boss',
            'username' => 'boss',
            'email' => 'boss@example.com',
        ])->assertSessionHasNoErrors();

        $tenant->refresh();
        $this->assertSame('boss', $tenant->username);
        $this->assertSame('boss@example.com', $tenant->email);
    }

    public function test_super_admin_can_update_their_profile(): void
    {
        $super = User::factory()->create(['role' => 'super_admin', 'email' => 'sa@example.com']);

        $this->actingAs($super)->post('/profile', [
            'name' => 'Overlord',
            'email' => 'sa@example.com',
        ])->assertSessionHas('success');

        $this->assertSame('Overlord', $super->fresh()->name);
    }

    public function test_password_is_kept_when_left_blank(): void
    {
        $tenant = $this->makeTenantAdmin();
        $judge = $this->makeJudge($tenant, 'nova');
        $hash = $judge->password;

        $this->actingAs($judge)->post('/profile', [
            'name' => $judge->name,
            'username' => 'nova',
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame($hash, $judge->fresh()->password);
    }

    public function test_password_changes_with_the_correct_current_password(): void
    {
        $tenant = $this->makeTenantAdmin();
        $judge = User::factory()->create([
            'role' => 'judge', 'owner_id' => $tenant->id, 'email' => null, 'username' => 'nova',
            'password' => Hash::make('old-password-1'),
        ]);

        $this->actingAs($judge)->post('/profile', [
            'name' => $judge->name,
            'username' => 'nova',
            'current_password' => 'old-password-1',
            'password' => 'brand-new-pass-2',
            'password_confirmation' => 'brand-new-pass-2',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('brand-new-pass-2', $judge->fresh()->password));
    }

    public function test_password_change_rejected_without_current_password(): void
    {
        $tenant = $this->makeTenantAdmin();
        $judge = $this->makeJudge($tenant, 'nova');

        $this->actingAs($judge)->post('/profile', [
            'name' => $judge->name,
            'username' => 'nova',
            'password' => 'brand-new-pass-2',
            'password_confirmation' => 'brand-new-pass-2',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_username_must_stay_unique(): void
    {
        $tenant = $this->makeTenantAdmin();
        $this->makeJudge($tenant, 'taken');
        $judge = $this->makeJudge($tenant, 'mine');

        $this->actingAs($judge)->post('/profile', [
            'name' => $judge->name,
            'username' => 'taken',
        ])->assertSessionHasErrors('username');
    }

    public function test_email_must_stay_unique(): void
    {
        $tenant = $this->makeTenantAdmin();
        $other = $this->makeJudge($tenant, 'other');
        $other->update(['email' => 'dup@example.com']);
        $judge = $this->makeJudge($tenant, 'mine');

        $this->actingAs($judge)->post('/profile', [
            'name' => $judge->name,
            'username' => 'mine',
            'email' => 'dup@example.com',
        ])->assertSessionHasErrors('email');
    }

    public function test_profile_tab_is_in_the_bottom_nav_for_everyone(): void
    {
        $tenant = $this->makeTenantAdmin();

        $this->actingAs($tenant)->get('/')
            ->assertOk()
            ->assertSee(route('profile.edit'));
    }
}
