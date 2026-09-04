<?php

namespace Tests\Feature;

use App\Models\Contest;
use App\Models\Submission;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantJudgeTest extends TestCase
{
    use RefreshDatabase;

    private function contest(User $tenant, string $name, string $status = 'active'): Contest
    {
        $c = Contest::create([
            'owner_id' => $tenant->id,
            'name' => $name,
            'status' => $status,
            'contest_type' => 'image',
        ]);
        $c->criteria()->create(['name' => 'Composition', 'max_score' => 10, 'sort_order' => 1]);

        return $c;
    }

    private function submission(Contest $contest): Submission
    {
        return Submission::create([
            'contest_id' => $contest->id,
            'character_name' => 'A',
            'discord_user' => 'a#'.$contest->id,
            'gender' => 'Female',
            'country' => 'JP',
            'style' => 'Anime',
            'backstory' => 'b',
        ]);
    }

    public function test_username_is_globally_unique_when_creating_a_new_judge(): void
    {
        $tenantA = User::factory()->create(['role' => 'tenant_admin']);
        $tenantB = User::factory()->create(['role' => 'tenant_admin']);
        User::factory()->create(['role' => 'judge', 'owner_id' => $tenantA->id, 'email' => null, 'username' => 'nova']);

        // tenantB tries to create a *new* account with a taken username -> invite prompt, no create
        $this->actingAs($tenantB)
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => 'Different Person',
                'username' => 'nova',
                'password' => 'secret-password-1',
                'password_confirmation' => 'secret-password-1',
            ])
            ->assertRedirect('/admin/users/create')
            ->assertSessionHas('invite_prompt');

        $this->assertSame(1, User::where('username', 'nova')->count());

        // the create screen shows the "invite instead?" modal
        $this->actingAs($tenantB)->get('/admin/users/create')
            ->assertOk()
            ->assertSee('This judge already exists')
            ->assertSee('Yes, invite');
    }

    public function test_create_form_offers_invite_and_invite_adds_membership_without_touching_password(): void
    {
        $tenantA = User::factory()->create(['role' => 'tenant_admin']);
        $tenantB = User::factory()->create(['role' => 'tenant_admin']);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $tenantA->id, 'email' => null, 'username' => 'nova']);
        $originalHash = $judge->password;

        $this->actingAs($tenantB)->post('/admin/users', [
            'name' => 'X',
            'username' => 'nova',
            'password' => 'irrelevant-123',
            'password_confirmation' => 'irrelevant-123',
        ])->assertSessionHas('invite_prompt');

        $this->actingAs($tenantB)->post('/admin/users/invite', ['user_id' => $judge->id])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertTrue($judge->fresh()->belongsToTenant($tenantB->id));
        $this->assertSame($originalHash, $judge->fresh()->password);
        $this->assertSame(2, TenantMembership::where('user_id', $judge->id)->count());
    }

    public function test_invite_is_idempotent(): void
    {
        $tenantA = User::factory()->create(['role' => 'tenant_admin']);
        $tenantB = User::factory()->create(['role' => 'tenant_admin']);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $tenantA->id, 'email' => null, 'username' => 'nova']);

        $this->actingAs($tenantB)->post('/admin/users/invite', ['user_id' => $judge->id]);
        $this->actingAs($tenantB)->post('/admin/users/invite', ['user_id' => $judge->id]);

        $this->assertSame(1, TenantMembership::where('tenant_id', $tenantB->id)->where('user_id', $judge->id)->count());
    }

    public function test_cannot_invite_a_tenant_admin_as_a_judge(): void
    {
        $tenantA = User::factory()->create(['role' => 'tenant_admin', 'username' => 'boss']);
        $tenantB = User::factory()->create(['role' => 'tenant_admin']);

        // Trying to create with a tenant admin's username -> plain "taken" error, not an invite
        $this->actingAs($tenantB)->post('/admin/users', [
            'name' => 'X', 'username' => 'boss',
            'password' => 'secret-password-1', 'password_confirmation' => 'secret-password-1',
        ])->assertSessionHasErrors('username');

        $this->actingAs($tenantB)->post('/admin/users/invite', ['user_id' => $tenantA->id])->assertForbidden();
    }

    public function test_a_co_admin_of_one_tenant_can_be_invited_as_a_judge_to_another(): void
    {
        $tenantA = $this->makeTenantAdmin();
        $tenantB = $this->makeTenantAdmin();
        $coAdmin = $this->makeCoAdmin($tenantA, 'nova');

        $this->actingAs($tenantB)->post('/admin/users/invite', ['user_id' => $coAdmin->id])
            ->assertSessionHas('success');

        $this->assertSame('co_admin', $coAdmin->memberships()->where('tenant_id', $tenantA->id)->value('role'));
        $this->assertSame('judge', $coAdmin->memberships()->where('tenant_id', $tenantB->id)->value('role'));
    }

    public function test_multi_tenant_judge_sees_contests_from_every_member_tenant(): void
    {
        $tenantA = User::factory()->create(['role' => 'tenant_admin']);
        $tenantB = User::factory()->create(['role' => 'tenant_admin']);
        $tenantC = User::factory()->create(['role' => 'tenant_admin']);

        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $tenantA->id, 'email' => null, 'username' => 'nova']);
        $judge->memberships()->create(['tenant_id' => $tenantB->id]);

        $cA = $this->contest($tenantA, 'A Cup');
        $cB = $this->contest($tenantB, 'B Cup');
        $cC = $this->contest($tenantC, 'C Cup'); // judge is NOT a member here

        $html = $this->actingAs($judge)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('A Cup', $html);
        $this->assertStringContainsString('B Cup', $html);
        $this->assertStringNotContainsString('C Cup', $html);
        // owner badge is shown to judges
        $this->assertStringContainsString($tenantA->name, $html);
        $this->assertStringContainsString($tenantB->name, $html);
    }

    public function test_multi_tenant_judge_can_vote_in_an_invited_tenants_contest(): void
    {
        $tenantA = User::factory()->create(['role' => 'tenant_admin']);
        $tenantB = User::factory()->create(['role' => 'tenant_admin']);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $tenantA->id, 'email' => null, 'username' => 'nova']);
        $judge->memberships()->create(['tenant_id' => $tenantB->id]);

        $contestB = $this->contest($tenantB, 'B Cup');
        $submissionB = $this->submission($contestB);

        $this->actingAs($judge)->get("/contests/{$contestB->id}/vote/{$submissionB->id}")->assertOk();
        $this->actingAs($judge)
            ->post("/contests/{$contestB->id}/vote/{$submissionB->id}", [
                'scores' => [$contestB->criteria->first()->id => 8], 'comment' => null,
            ])
            ->assertRedirect(route('judge.voting.index', $contestB));

        $this->assertSame(8, (int) $submissionB->votes()->where('user_id', $judge->id)->value('total_score'));
    }

    public function test_judge_still_cannot_touch_a_non_member_tenants_contest(): void
    {
        $tenantA = User::factory()->create(['role' => 'tenant_admin']);
        $tenantC = User::factory()->create(['role' => 'tenant_admin']);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $tenantA->id, 'email' => null, 'username' => 'nova']);

        $contestC = $this->contest($tenantC, 'C Cup');
        $submissionC = $this->submission($contestC);

        $this->actingAs($judge)->get("/contests/{$contestC->id}/vote")->assertNotFound();
        $this->actingAs($judge)
            ->post("/contests/{$contestC->id}/vote/{$submissionC->id}", [
                'scores' => [$contestC->criteria->first()->id => 5], 'comment' => null,
            ])
            ->assertNotFound();
    }

    public function test_removing_a_judge_keeps_closed_contest_votes_and_the_account(): void
    {
        $tenantA = User::factory()->create(['role' => 'tenant_admin']);
        $tenantB = User::factory()->create(['role' => 'tenant_admin']);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $tenantA->id, 'email' => null, 'username' => 'nova']);
        $judge->memberships()->create(['tenant_id' => $tenantB->id, 'role' => 'judge']);

        $closed = $this->contest($tenantA, 'A Closed', 'closed');
        $open = $this->contest($tenantA, 'A Open', 'active');
        $vClosed = Vote::create(['user_id' => $judge->id, 'submission_id' => $this->submission($closed)->id, 'total_score' => 5]);
        $vOpen = Vote::create(['user_id' => $judge->id, 'submission_id' => $this->submission($open)->id, 'total_score' => 7]);

        // Default: keep votes on open contests too
        $this->actingAs($tenantA)->delete("/admin/users/{$judge->id}")->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $judge->id]);
        $this->assertFalse($judge->fresh()->belongsToTenant($tenantA->id));
        $this->assertTrue($judge->fresh()->belongsToTenant($tenantB->id));
        $this->assertDatabaseHas('votes', ['id' => $vClosed->id]);
        $this->assertDatabaseHas('votes', ['id' => $vOpen->id]);
    }

    public function test_removing_a_judge_with_delete_open_votes_drops_only_the_open_ones(): void
    {
        $tenantA = User::factory()->create(['role' => 'tenant_admin']);
        $tenantB = User::factory()->create(['role' => 'tenant_admin']);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $tenantA->id, 'email' => null, 'username' => 'nova']);
        $judge->memberships()->create(['tenant_id' => $tenantB->id, 'role' => 'judge']);

        $closed = $this->contest($tenantA, 'A Closed', 'closed');
        $open = $this->contest($tenantA, 'A Open', 'active');
        $vClosed = Vote::create(['user_id' => $judge->id, 'submission_id' => $this->submission($closed)->id, 'total_score' => 5]);
        $vOpen = Vote::create(['user_id' => $judge->id, 'submission_id' => $this->submission($open)->id, 'total_score' => 7]);

        $this->actingAs($tenantA)->delete("/admin/users/{$judge->id}", ['delete_open_votes' => 1])->assertSessionHas('success');

        $this->assertDatabaseHas('votes', ['id' => $vClosed->id]);
        $this->assertDatabaseMissing('votes', ['id' => $vOpen->id]);
    }

    public function test_removing_a_judge_from_their_last_tenant_deletes_the_account(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant_admin']);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $tenant->id, 'email' => null, 'username' => 'solo']);

        $this->actingAs($tenant)->delete("/admin/users/{$judge->id}")->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $judge->id]);
    }

    public function test_tenant_admin_can_reset_password_only_for_a_single_tenant_judge(): void
    {
        $tenantA = User::factory()->create(['role' => 'tenant_admin']);
        $tenantB = User::factory()->create(['role' => 'tenant_admin']);

        $solo = User::factory()->create(['role' => 'judge', 'owner_id' => $tenantA->id, 'email' => null, 'username' => 'solo']);
        $shared = User::factory()->create(['role' => 'judge', 'owner_id' => $tenantA->id, 'email' => null, 'username' => 'shared']);
        $shared->memberships()->create(['tenant_id' => $tenantB->id, 'role' => 'judge']);

        $this->actingAs($tenantA)->post("/admin/users/{$solo->id}/reset-password", [
            'password' => 'brand-new-pass-1', 'password_confirmation' => 'brand-new-pass-1',
        ])->assertSessionHas('success');

        $this->actingAs($tenantA)->post("/admin/users/{$shared->id}/reset-password", [
            'password' => 'brand-new-pass-2', 'password_confirmation' => 'brand-new-pass-2',
        ])->assertForbidden();
    }

    public function test_remove_modal_states_closed_contest_votes_are_kept(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant_admin']);
        $this->makeJudge($tenant, 'nova');

        $this->actingAs($tenant)->get('/admin/users')
            ->assertOk()
            ->assertSee("won't delete their votes on closed contests", false);
    }

    public function test_a_multi_tenant_judge_can_be_promoted_to_co_admin_of_one_tenant_only(): void
    {
        $tenantA = User::factory()->create(['role' => 'tenant_admin']);
        $tenantB = User::factory()->create(['role' => 'tenant_admin']);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $tenantA->id, 'email' => null, 'username' => 'nova']);
        $judge->memberships()->create(['tenant_id' => $tenantB->id, 'role' => 'judge']);

        $this->actingAs($tenantA)->post("/admin/users/{$judge->id}/promote")->assertSessionHas('success');

        $this->assertSame('co_admin', $judge->memberships()->where('tenant_id', $tenantA->id)->value('role'));
        $this->assertSame('judge', $judge->memberships()->where('tenant_id', $tenantB->id)->value('role'));
    }
}
