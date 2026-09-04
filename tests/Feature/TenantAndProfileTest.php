<?php

namespace Tests\Feature;

use App\Models\Contest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantAndProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_cannot_delete_a_tenant_that_still_owns_a_contest(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);
        $tenant = User::factory()->create(['role' => 'tenant_admin']);
        Contest::create(['owner_id' => $tenant->id, 'name' => 'C', 'status' => 'draft', 'contest_type' => 'image']);

        $this->actingAs($super)
            ->delete("/super-admin/tenants/{$tenant->id}")
            ->assertRedirect(route('super-admin.tenants.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $tenant->id]);
    }

    public function test_super_admin_cannot_delete_a_tenant_that_still_owns_a_judge(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);
        $tenant = User::factory()->create(['role' => 'tenant_admin']);
        User::factory()->create(['role' => 'judge', 'owner_id' => $tenant->id, 'email' => null, 'username' => 'j']);

        $this->actingAs($super)->delete("/super-admin/tenants/{$tenant->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $tenant->id]);
    }

    public function test_super_admin_can_delete_an_empty_tenant(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);
        $tenant = User::factory()->create(['role' => 'tenant_admin']);

        $this->actingAs($super)
            ->delete("/super-admin/tenants/{$tenant->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $tenant->id]);
    }

    public function test_profile_is_reachable_by_every_signed_in_user(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);
        $tenant = $this->makeTenantAdmin();
        $coAdmin = $this->makeCoAdmin($tenant, 'c');
        $judge = $this->makeJudge($tenant, 'j');

        foreach ([$super, $tenant, $coAdmin, $judge] as $user) {
            $this->actingAs($user)->get('/profile')->assertOk();
        }
    }
}
