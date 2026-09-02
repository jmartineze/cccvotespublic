<?php

namespace Tests\Feature;

use App\Models\Contest;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VotingTest extends TestCase
{
    use RefreshDatabase;

    private function makeContest(string $type = 'image', string $status = 'active', ?User $tenant = null): Contest
    {
        $tenant ??= User::factory()->create(['role' => 'tenant_admin']);
        $contest = Contest::create([
            'owner_id' => $tenant->id,
            'name' => 'Test Contest',
            'status' => $status,
            'contest_type' => $type,
        ]);
        $contest->criteria()->createMany([
            ['name' => 'Composition', 'max_score' => 10, 'sort_order' => 1],
            ['name' => 'Cultural', 'max_score' => 20, 'sort_order' => 2],
            ['name' => 'Allure', 'max_score' => 10, 'sort_order' => 3],
        ]);

        return $contest;
    }

    private function makeSubmission(Contest $contest): Submission
    {
        return Submission::create([
            'contest_id' => $contest->id,
            'character_name' => 'Aiko',
            'discord_user' => 'aiko#1',
            'gender' => 'Female',
            'country' => 'Japan',
            'style' => 'Anime',
            'backstory' => 'A story.',
        ]);
    }

    public function test_judge_can_cast_a_vote_and_total_is_persisted(): void
    {
        $contest = $this->makeContest();
        $submission = $this->makeSubmission($contest);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $contest->owner_id, 'email' => null, 'username' => 'j1']);

        $scores = [];
        foreach ($contest->criteria as $c) {
            $scores[$c->id] = 4;
        }

        $this->actingAs($judge)
            ->post("/contests/{$contest->id}/vote/{$submission->id}", ['scores' => $scores, 'comment' => null])
            ->assertRedirect(route('judge.voting.index', $contest));

        $vote = $submission->votes()->where('user_id', $judge->id)->firstOrFail();

        $this->assertSame(3, $vote->voteScores()->count());
        $this->assertSame(12, (int) $vote->total_score);
        $this->assertSame(12, (int) $vote->voteScores()->sum('score'));
    }

    public function test_updating_a_vote_recalculates_the_total(): void
    {
        $contest = $this->makeContest();
        $submission = $this->makeSubmission($contest);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $contest->owner_id, 'email' => null, 'username' => 'j2']);

        $first = [];
        $second = [];
        foreach ($contest->criteria as $c) {
            $first[$c->id] = 2;
            $second[$c->id] = 5;
        }

        $this->actingAs($judge)->post("/contests/{$contest->id}/vote/{$submission->id}", ['scores' => $first, 'comment' => null]);
        $this->actingAs($judge)->post("/contests/{$contest->id}/vote/{$submission->id}", ['scores' => $second, 'comment' => null]);

        $vote = $submission->votes()->where('user_id', $judge->id)->firstOrFail();
        $this->assertSame(1, $submission->votes()->where('user_id', $judge->id)->count());
        $this->assertSame(15, (int) $vote->total_score);
    }

    public function test_score_above_criterion_max_is_rejected(): void
    {
        $contest = $this->makeContest();
        $submission = $this->makeSubmission($contest);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $contest->owner_id, 'email' => null, 'username' => 'j3']);

        $scores = [];
        foreach ($contest->criteria as $c) {
            $scores[$c->id] = $c->max_score + 1;
        }

        $this->actingAs($judge)
            ->post("/contests/{$contest->id}/vote/{$submission->id}", ['scores' => $scores, 'comment' => null])
            ->assertSessionHasErrors();

        $this->assertSame(0, $submission->votes()->count());
    }

    public function test_voting_detail_renders_valid_alpine_scores_json(): void
    {
        $contest = $this->makeContest();
        $submission = $this->makeSubmission($contest);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $contest->owner_id, 'email' => null, 'username' => 'j4']);

        $html = $this->actingAs($judge)
            ->get("/contests/{$contest->id}/vote/{$submission->id}")
            ->assertOk()
            ->getContent();

        // The Alpine bootstrap JSON must not be HTML-escaped inside <script>
        $this->assertStringNotContainsString('&quot;', $html);
        $ids = $contest->criteria->pluck('id');
        $this->assertStringContainsString('scores: {"'.$ids[0].'":', $html);
    }

    public function test_judge_cannot_vote_on_a_draft_contest(): void
    {
        $contest = $this->makeContest('image', 'draft');
        $submission = $this->makeSubmission($contest);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $contest->owner_id, 'email' => null, 'username' => 'jd']);

        $scores = $contest->criteria->mapWithKeys(fn ($c) => [$c->id => 3])->all();

        $this->actingAs($judge)
            ->post("/contests/{$contest->id}/vote/{$submission->id}", ['scores' => $scores, 'comment' => null])
            ->assertForbidden();

        $this->assertSame(0, $submission->votes()->count());
    }

    public function test_judge_cannot_vote_on_a_closed_contest(): void
    {
        $contest = $this->makeContest('image', 'closed');
        $submission = $this->makeSubmission($contest);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $contest->owner_id, 'email' => null, 'username' => 'jc']);

        $scores = $contest->criteria->mapWithKeys(fn ($c) => [$c->id => 3])->all();

        $this->actingAs($judge)
            ->post("/contests/{$contest->id}/vote/{$submission->id}", ['scores' => $scores, 'comment' => null])
            ->assertForbidden();

        $this->assertSame(0, $submission->votes()->count());
    }

    public function test_judge_cannot_open_the_voting_screen_of_a_draft_contest(): void
    {
        $contest = $this->makeContest('image', 'draft');
        $submission = $this->makeSubmission($contest);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $contest->owner_id, 'email' => null, 'username' => 'jv']);

        $this->actingAs($judge)->get("/contests/{$contest->id}/vote")->assertNotFound();
        $this->actingAs($judge)->get("/contests/{$contest->id}/vote/{$submission->id}")->assertNotFound();
    }

    public function test_judge_cannot_vote_in_another_tenants_contest(): void
    {
        $contestA = $this->makeContest();
        $contestB = $this->makeContest();
        $submissionB = $this->makeSubmission($contestB);

        // Judge belongs to tenant A only
        $judgeA = User::factory()->create(['role' => 'judge', 'owner_id' => $contestA->owner_id, 'email' => null, 'username' => 'ja']);

        $scoresB = $contestB->criteria->mapWithKeys(fn ($c) => [$c->id => 3])->all();
        $scoresA = $contestA->criteria->mapWithKeys(fn ($c) => [$c->id => 3])->all();

        // Contest B is not visible to a tenant-A judge → route binding 404s
        $this->actingAs($judgeA)
            ->post("/contests/{$contestB->id}/vote/{$submissionB->id}", ['scores' => $scoresB, 'comment' => null])
            ->assertNotFound();

        // Own contest A but a submission that lives in another contest → 404
        $this->actingAs($judgeA)
            ->post("/contests/{$contestA->id}/vote/{$submissionB->id}", ['scores' => $scoresA, 'comment' => null])
            ->assertNotFound();

        $this->assertSame(0, $submissionB->votes()->count());
    }

    public function test_vote_is_always_recorded_for_the_authenticated_judge(): void
    {
        $contest = $this->makeContest();
        $submission = $this->makeSubmission($contest);
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $contest->owner_id, 'email' => null, 'username' => 'me']);
        $other = User::factory()->create(['role' => 'judge', 'owner_id' => $contest->owner_id, 'email' => null, 'username' => 'other']);

        $scores = $contest->criteria->mapWithKeys(fn ($c) => [$c->id => 3])->all();

        // Attempt to spoof another judge via request body
        $this->actingAs($judge)->post("/contests/{$contest->id}/vote/{$submission->id}", [
            'scores' => $scores,
            'comment' => null,
            'user_id' => $other->id,
        ]);

        $this->assertSame(1, $submission->votes()->where('user_id', $judge->id)->count());
        $this->assertSame(0, $submission->votes()->where('user_id', $other->id)->count());
    }
}
