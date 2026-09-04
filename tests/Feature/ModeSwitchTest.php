<?php

namespace Tests\Feature;

use App\Models\Contest;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModeSwitchTest extends TestCase
{
    use RefreshDatabase;

    private function activeContestWithSubmission(User $tenant): array
    {
        $contest = Contest::create([
            'owner_id' => $tenant->id,
            'name' => 'C',
            'status' => 'active',
            'contest_type' => 'image',
        ]);
        $contest->criteria()->create(['name' => 'Composition', 'max_score' => 10, 'sort_order' => 1]);
        $submission = Submission::create([
            'contest_id' => $contest->id,
            'character_name' => 'A',
            'discord_user' => 'a#1',
            'gender' => 'Female',
            'country' => 'JP',
            'style' => 'Anime',
            'backstory' => 'b',
        ]);

        return [$contest, $submission];
    }

    public function test_tenant_admin_cannot_vote_in_admin_mode(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant_admin']);
        [$contest, $submission] = $this->activeContestWithSubmission($tenant);

        $this->actingAs($tenant)->get("/contests/{$contest->id}/vote")->assertForbidden();
        $this->actingAs($tenant)
            ->post("/contests/{$contest->id}/vote/{$submission->id}", [
                'scores' => [$contest->criteria->first()->id => 5], 'comment' => null,
            ])
            ->assertForbidden();
    }

    public function test_tenant_admin_toggles_into_judge_mode_and_can_vote(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant_admin']);
        [$contest, $submission] = $this->activeContestWithSubmission($tenant);

        $this->actingAs($tenant)->post('/mode/toggle')->assertRedirect(route('dashboard'));
        $this->assertTrue($tenant->fresh()->judge_mode);
        $this->assertTrue($tenant->fresh()->inJudgeMode());

        $this->actingAs($tenant->fresh())->get("/contests/{$contest->id}/vote")->assertOk();

        $this->actingAs($tenant->fresh())
            ->post("/contests/{$contest->id}/vote/{$submission->id}", [
                'scores' => [$contest->criteria->first()->id => 7], 'comment' => null,
            ])
            ->assertRedirect(route('judge.voting.index', $contest));

        $this->assertSame(7, (int) $submission->votes()->where('user_id', $tenant->id)->value('total_score'));
    }

    public function test_judge_mode_admin_is_locked_out_of_the_admin_panel(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant_admin', 'judge_mode' => true]);

        $this->actingAs($tenant)->get('/admin/contests')->assertForbidden();
    }

    public function test_toggling_back_restores_admin_access_and_blocks_voting(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant_admin', 'judge_mode' => true]);
        [$contest] = $this->activeContestWithSubmission($tenant);

        $this->actingAs($tenant)->post('/mode/toggle');
        $this->assertFalse($tenant->fresh()->judge_mode);

        $this->actingAs($tenant->fresh())->get('/admin/contests')->assertOk();
        $this->actingAs($tenant->fresh())->get("/contests/{$contest->id}/vote")->assertForbidden();
    }

    public function test_a_plain_judge_cannot_toggle_mode(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant_admin']);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $tenant->id, 'email' => null, 'username' => 'j']);

        $this->actingAs($judge)->post('/mode/toggle')->assertForbidden();
    }

    public function test_super_admin_cannot_toggle_mode(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($super)->post('/mode/toggle')->assertForbidden();
    }

    public function test_dashboard_shows_the_switch_control_and_judge_mode_banner(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant_admin']);

        $this->actingAs($tenant)->get('/')
            ->assertOk()
            ->assertSee('Judge view')
            ->assertDontSee("You're in <strong>judge mode</strong>", false);

        $tenant->update(['judge_mode' => true]);

        $this->actingAs($tenant->fresh())->get('/')
            ->assertOk()
            ->assertSee('Judge mode · Exit')
            ->assertSee("You're in <strong>judge mode</strong>", false);
    }
}
