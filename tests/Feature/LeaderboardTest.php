<?php

namespace Tests\Feature;

use App\Models\Contest;
use App\Models\Submission;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiebreak_criterion_orders_submissions_with_equal_totals(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant_admin']);
        $contest = Contest::create([
            'owner_id' => $tenant->id,
            'name' => 'C',
            'status' => 'closed',
            'contest_type' => 'image',
        ]);
        $composition = $contest->criteria()->create(['name' => 'Composition', 'max_score' => 10, 'sort_order' => 1]);
        $culture = $contest->criteria()->create(['name' => 'Culture', 'max_score' => 20, 'sort_order' => 2, 'tiebreak_order' => 1]);

        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $tenant->id, 'email' => null, 'username' => 'j']);

        // Two submissions, identical totals (20) but different Culture sub-scores.
        $low = Submission::create(['contest_id' => $contest->id, 'character_name' => 'Low', 'discord_user' => 'l#1', 'gender' => 'Female', 'country' => 'JP', 'style' => 'Anime', 'backstory' => 'b']);
        $high = Submission::create(['contest_id' => $contest->id, 'character_name' => 'High', 'discord_user' => 'h#1', 'gender' => 'Female', 'country' => 'JP', 'style' => 'Anime', 'backstory' => 'b']);

        foreach ([[$low, 15, 5], [$high, 5, 15]] as [$sub, $comp, $cult]) {
            $vote = Vote::create(['user_id' => $judge->id, 'submission_id' => $sub->id, 'total_score' => 0]);
            VoteScore::create(['vote_id' => $vote->id, 'contest_criterion_id' => $composition->id, 'score' => $comp]);
            VoteScore::create(['vote_id' => $vote->id, 'contest_criterion_id' => $culture->id, 'score' => $cult]);
            $vote->recalculateTotalScore();
        }

        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->get("/results/{$contest->id}")->assertOk();

        $categories = $response->viewData('categories');
        $ranked = $categories['Female Anime'];

        $this->assertSame('High', $ranked[0]->character_name, 'Higher tiebreak (Culture) score should rank first');
        $this->assertSame('Low', $ranked[1]->character_name);
    }
}
