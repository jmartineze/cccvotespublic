<?php

namespace Tests\Feature;

use App\Models\Contest;
use App\Models\SpecialPrizeVote;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialPrizeTest extends TestCase
{
    use RefreshDatabase;

    private function contest(User $tenant, string $status = 'active'): Contest
    {
        $c = Contest::create([
            'owner_id' => $tenant->id, 'name' => 'C', 'status' => $status, 'contest_type' => 'image',
        ]);
        $c->criteria()->create(['name' => 'Composition', 'max_score' => 10, 'sort_order' => 1]);

        return $c;
    }

    private function submission(Contest $c, string $name): Submission
    {
        return Submission::create([
            'contest_id' => $c->id, 'character_name' => $name, 'discord_user' => strtolower($name).'#1',
            'gender' => 'Female', 'country' => 'JP', 'style' => 'Anime', 'backstory' => 'b',
        ]);
    }

    public function test_admin_creates_a_contest_with_special_prizes(): void
    {
        $tenant = $this->makeTenantAdmin();

        $this->actingAs($tenant)->post('/admin/contests', [
            'name' => 'S1', 'status' => 'draft', 'contest_type' => 'image',
            'criteria' => [['name' => 'Composition', 'max_score' => 10]],
            'special_prizes' => [
                ['name' => 'Best image', 'description' => 'Visual craft'],
                ['name' => 'Made me laugh', 'description' => ''],
            ],
        ])->assertRedirect(route('admin.contests.index'));

        $contest = Contest::withoutGlobalScopes()->where('name', 'S1')->firstOrFail();
        $this->assertSame(['Best image', 'Made me laugh'], $contest->specialPrizes->pluck('name')->all());
    }

    public function test_special_prizes_stay_editable_after_voting_and_keep_their_votes(): void
    {
        $tenant = $this->makeTenantAdmin();
        $contest = $this->contest($tenant);
        $keep = $contest->specialPrizes()->create(['name' => 'Best image', 'sort_order' => 0]);
        $drop = $contest->specialPrizes()->create(['name' => 'Typo prize', 'sort_order' => 1]);
        $sub = $this->submission($contest, 'Aiko');
        $judge = $this->makeJudge($tenant, 'j');

        SpecialPrizeVote::create(['special_prize_id' => $keep->id, 'user_id' => $judge->id, 'submission_id' => $sub->id]);
        // a real numeric vote so hasVotes() is true
        $contest->submissions()->first()->votes()->create(['user_id' => $judge->id, 'total_score' => 3]);

        $this->actingAs($tenant)->put("/admin/contests/{$contest->id}", [
            'name' => $contest->name, 'status' => 'active', 'contest_type' => 'image',
            'special_prizes' => [
                ['id' => $keep->id, 'name' => 'Best image (renamed)', 'description' => ''],
                ['name' => 'Fresh prize', 'description' => ''],
            ],
        ])->assertRedirect(route('admin.contests.index'));

        $this->assertSame('Best image (renamed)', $keep->fresh()->name);
        $this->assertDatabaseMissing('special_prizes', ['id' => $drop->id]);
        $this->assertDatabaseHas('special_prizes', ['contest_id' => $contest->id, 'name' => 'Fresh prize']);
        // vote on the kept prize survives
        $this->assertDatabaseHas('special_prize_votes', ['special_prize_id' => $keep->id, 'user_id' => $judge->id]);
    }

    public function test_judge_toggles_a_special_prize_on_and_off(): void
    {
        $tenant = $this->makeTenantAdmin();
        $contest = $this->contest($tenant);
        $prize = $contest->specialPrizes()->create(['name' => 'Best image', 'sort_order' => 0]);
        $sub = $this->submission($contest, 'Aiko');
        $judge = $this->makeJudge($tenant, 'j');

        $this->actingAs($judge)->post("/contests/{$contest->id}/special-prize/{$prize->id}/{$sub->id}");
        $this->assertDatabaseHas('special_prize_votes', [
            'special_prize_id' => $prize->id, 'user_id' => $judge->id, 'submission_id' => $sub->id,
        ]);

        $this->actingAs($judge)->post("/contests/{$contest->id}/special-prize/{$prize->id}/{$sub->id}");
        $this->assertDatabaseMissing('special_prize_votes', [
            'special_prize_id' => $prize->id, 'user_id' => $judge->id, 'submission_id' => $sub->id,
        ]);
    }

    public function test_judge_can_mark_many_submissions_across_many_prizes(): void
    {
        $tenant = $this->makeTenantAdmin();
        $contest = $this->contest($tenant);
        $p1 = $contest->specialPrizes()->create(['name' => 'A', 'sort_order' => 0]);
        $p2 = $contest->specialPrizes()->create(['name' => 'B', 'sort_order' => 1]);
        $s1 = $this->submission($contest, 'One');
        $s2 = $this->submission($contest, 'Two');
        $judge = $this->makeJudge($tenant, 'j');

        foreach ([[$p1, $s1], [$p1, $s2], [$p2, $s1]] as [$p, $s]) {
            $this->actingAs($judge)->post("/contests/{$contest->id}/special-prize/{$p->id}/{$s->id}");
        }

        $this->assertSame(3, SpecialPrizeVote::where('user_id', $judge->id)->count());
    }

    public function test_admin_and_closed_contest_and_cross_contest_are_blocked(): void
    {
        $tenant = $this->makeTenantAdmin();
        $active = $this->contest($tenant);
        $closed = $this->contest($tenant, 'closed');
        $prize = $active->specialPrizes()->create(['name' => 'A', 'sort_order' => 0]);
        $otherPrize = $closed->specialPrizes()->create(['name' => 'B', 'sort_order' => 0]);
        $sub = $this->submission($active, 'Aiko');
        $judge = $this->makeJudge($tenant, 'j');

        // admin in admin mode
        $this->actingAs($tenant)->post("/contests/{$active->id}/special-prize/{$prize->id}/{$sub->id}")->assertForbidden();
        // closed contest
        $closedSub = $this->submission($closed, 'Bibi');
        $this->actingAs($judge)->post("/contests/{$closed->id}/special-prize/{$otherPrize->id}/{$closedSub->id}")->assertForbidden();
        // prize from another contest
        $this->actingAs($judge)->post("/contests/{$active->id}/special-prize/{$otherPrize->id}/{$sub->id}")->assertNotFound();

        $this->assertSame(0, SpecialPrizeVote::count());
    }

    public function test_results_rank_submissions_by_check_count_and_hide_zero(): void
    {
        $tenant = $this->makeTenantAdmin();
        $contest = $this->contest($tenant, 'closed');
        $prize = $contest->specialPrizes()->create(['name' => 'Best image', 'sort_order' => 0]);

        $winner = $this->submission($contest, 'Winner');
        $second = $this->submission($contest, 'Second');
        $this->submission($contest, 'Ignored'); // zero checks — must not appear

        $judges = collect(range(1, 3))->map(fn ($i) => $this->makeJudge($tenant, "j{$i}"));
        // Winner gets 3 checks, Second gets 1
        foreach ($judges as $j) {
            SpecialPrizeVote::create(['special_prize_id' => $prize->id, 'user_id' => $j->id, 'submission_id' => $winner->id]);
        }
        SpecialPrizeVote::create(['special_prize_id' => $prize->id, 'user_id' => $judges[0]->id, 'submission_id' => $second->id]);

        $response = $this->actingAs($tenant)->get("/results/{$contest->id}")->assertOk();

        $ranked = $response->viewData('specialPrizeResults')->first()->rankedSubmissions;
        // ordered by checks desc; the zero-check submission is excluded entirely
        $this->assertSame(['Winner', 'Second'], $ranked->pluck('character_name')->all());
        $this->assertSame(3, (int) $ranked->first()->prize_checks);
        $this->assertFalse($ranked->contains('character_name', 'Ignored'));
    }

    public function test_voting_detail_renders_the_special_prize_toggles(): void
    {
        $tenant = $this->makeTenantAdmin();
        $contest = $this->contest($tenant);
        $prize = $contest->specialPrizes()->create(['name' => 'Made me laugh', 'description' => 'Funniest', 'sort_order' => 0]);
        $sub = $this->submission($contest, 'Aiko');
        $judge = $this->makeJudge($tenant, 'j');
        SpecialPrizeVote::create(['special_prize_id' => $prize->id, 'user_id' => $judge->id, 'submission_id' => $sub->id]);

        $this->actingAs($judge)->get("/contests/{$contest->id}/vote/{$sub->id}")
            ->assertOk()
            ->assertSee('Special Prizes')
            ->assertSee('Made me laugh')
            ->assertSee(route('judge.voting.special-prize', [$contest, $prize, $sub]));
    }

    public function test_a_judge_from_another_tenant_cannot_toggle(): void
    {
        $tenantA = $this->makeTenantAdmin();
        $tenantB = $this->makeTenantAdmin();
        $contest = $this->contest($tenantA);
        $prize = $contest->specialPrizes()->create(['name' => 'A', 'sort_order' => 0]);
        $sub = $this->submission($contest, 'Aiko');
        $outsider = $this->makeJudge($tenantB, 'out');

        $this->actingAs($outsider)->post("/contests/{$contest->id}/special-prize/{$prize->id}/{$sub->id}")->assertNotFound();
    }
}
