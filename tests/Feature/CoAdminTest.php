<?php

namespace Tests\Feature;

use App\Models\Contest;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_promotes_and_demotes_a_judge(): void
    {
        $tenant = $this->makeTenantAdmin();
        $judge = $this->makeJudge($tenant, 'nova');

        $this->actingAs($tenant)->post("/admin/users/{$judge->id}/promote")->assertSessionHas('success');
        $this->assertSame('co_admin', $judge->memberships()->where('tenant_id', $tenant->id)->value('role'));

        $this->actingAs($tenant)->post("/admin/users/{$judge->id}/demote")->assertSessionHas('success');
        $this->assertSame('judge', $judge->memberships()->where('tenant_id', $tenant->id)->value('role'));
    }

    public function test_demote_clears_judge_mode_when_no_co_admin_powers_remain(): void
    {
        $tenant = $this->makeTenantAdmin();
        $coAdmin = $this->makeCoAdmin($tenant, 'nova');
        $coAdmin->update(['judge_mode' => true]);

        $this->actingAs($tenant)->post("/admin/users/{$coAdmin->id}/demote");

        $this->assertFalse($coAdmin->fresh()->judge_mode);
        $this->assertSame('judge', $coAdmin->memberships()->where('tenant_id', $tenant->id)->value('role'));
    }

    public function test_co_admin_can_reach_admin_areas_but_not_the_super_admin_area(): void
    {
        $tenant = $this->makeTenantAdmin();
        $coAdmin = $this->makeCoAdmin($tenant, 'nova');

        $this->actingAs($coAdmin)->get('/admin/contests')->assertOk();
        $this->actingAs($coAdmin)->get('/admin/users')->assertOk();
        $this->actingAs($coAdmin)->get('/super-admin/tenants')->assertForbidden();
    }

    public function test_co_admin_cannot_promote_or_demote_anyone(): void
    {
        $tenant = $this->makeTenantAdmin();
        $coAdmin = $this->makeCoAdmin($tenant, 'boss');
        $judge = $this->makeJudge($tenant, 'nova');

        $this->actingAs($coAdmin)->post("/admin/users/{$judge->id}/promote")->assertForbidden();
        $this->actingAs($coAdmin)->post("/admin/users/{$coAdmin->id}/demote")->assertForbidden();
        $this->assertSame('judge', $judge->memberships()->where('tenant_id', $tenant->id)->value('role'));
    }

    public function test_co_admin_created_contest_belongs_to_the_tenant_not_the_co_admin(): void
    {
        $tenant = $this->makeTenantAdmin();
        $coAdmin = $this->makeCoAdmin($tenant, 'nova');

        $this->actingAs($coAdmin)->post('/admin/contests', [
            'name' => 'Season 2',
            'status' => 'draft',
            'contest_type' => 'image',
            'criteria' => [['name' => 'Composition', 'max_score' => 10]],
        ])->assertRedirect(route('admin.contests.index'));

        $contest = Contest::withoutGlobalScopes()->where('name', 'Season 2')->firstOrFail();
        $this->assertSame($tenant->id, $contest->owner_id);
    }

    public function test_co_admin_created_judge_belongs_to_the_tenant(): void
    {
        $tenant = $this->makeTenantAdmin();
        $coAdmin = $this->makeCoAdmin($tenant, 'nova');

        $this->actingAs($coAdmin)->post('/admin/users', [
            'name' => 'New Judge',
            'username' => 'newjudge',
            'password' => 'secret-password-123',
            'password_confirmation' => 'secret-password-123',
        ])->assertRedirect(route('admin.users.index'));

        $created = User::where('username', 'newjudge')->firstOrFail();
        $this->assertTrue($created->belongsToTenant($tenant->id));
        $this->assertSame('judge', $created->role);
    }

    public function test_promote_is_scoped_to_the_tenants_own_judges(): void
    {
        $tenantA = $this->makeTenantAdmin();
        $tenantB = $this->makeTenantAdmin();
        $judgeB = $this->makeJudge($tenantB, 'onlyb');

        $this->actingAs($tenantA)->post("/admin/users/{$judgeB->id}/promote")->assertForbidden();
        $this->assertSame('judge', $judgeB->memberships()->where('tenant_id', $tenantB->id)->value('role'));
    }

    public function test_a_judge_can_be_co_admin_of_more_than_one_tenant(): void
    {
        $tenantA = $this->makeTenantAdmin();
        $tenantB = $this->makeTenantAdmin();
        $judge = $this->makeJudge($tenantA, 'nova');
        $judge->memberships()->create(['tenant_id' => $tenantB->id, 'role' => 'judge']);

        $this->actingAs($tenantA)->post("/admin/users/{$judge->id}/promote")->assertSessionHas('success');
        $this->actingAs($tenantB)->post("/admin/users/{$judge->id}/promote")->assertSessionHas('success');

        $this->assertSame(2, TenantMembership::where('user_id', $judge->id)->where('role', 'co_admin')->count());
        $this->assertEqualsCanonicalizing([$tenantA->id, $tenantB->id], $judge->fresh()->adminTenantIds());
    }

    public function test_multi_tenant_co_admin_manages_the_tenant_chosen_in_the_session(): void
    {
        $tenantA = $this->makeTenantAdmin();
        $tenantB = $this->makeTenantAdmin();
        $judge = $this->makeCoAdmin($tenantA, 'nova');
        $judge->memberships()->create(['tenant_id' => $tenantB->id, 'role' => 'co_admin']);

        $contestA = Contest::create(['owner_id' => $tenantA->id, 'name' => 'A One', 'status' => 'draft', 'contest_type' => 'image']);
        $contestB = Contest::create(['owner_id' => $tenantB->id, 'name' => 'B One', 'status' => 'draft', 'contest_type' => 'image']);

        // Default context = first admin tenant
        $htmlA = $this->actingAs($judge)->get('/admin/contests')->assertOk()->getContent();
        $this->assertStringContainsString('A One', $htmlA);
        $this->assertStringNotContainsString('B One', $htmlA);

        // Switch context to tenant B
        $this->actingAs($judge)->post('/mode/tenant', ['tenant_id' => $tenantB->id]);
        $htmlB = $this->actingAs($judge)->get('/admin/contests')->assertOk()->getContent();
        $this->assertStringContainsString('B One', $htmlB);
        $this->assertStringNotContainsString('A One', $htmlB);
    }
}
