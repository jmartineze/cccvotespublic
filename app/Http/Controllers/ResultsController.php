<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\HonorableMention;
use App\Models\SpecialPrizeVote;
use App\Models\Submission;
use App\Models\Vote;
use App\Models\VoteScore;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ResultsController extends Controller
{
    public function index(): View
    {
        // Admins see all non-draft contests; judges only see active/closed
        $query = Contest::with('owner')->withCount('submissions')->latest();

        if (! auth()->user()->actingAsAdmin()) {
            $query->where('status', '!=', 'draft');
        }

        $contests = $query->paginate(15);

        return view('results.index', compact('contests'));
    }

    public function show(Contest $contest): View
    {
        abort_if($contest->status === 'draft' && ! auth()->user()->actingAsAdmin(), 403);

        $isAdmin = auth()->user()->actingAsAdmin();
        $isClosed = $contest->isClosed();

        // Results are visible in full only when: admin OR contest is closed
        $locked = ! $isAdmin && ! $isClosed;

        if ($locked) {
            // Show the judge's own votes so they can review what they scored
            $submissions = Submission::where('contest_id', $contest->id)
                ->with(['images', 'votes' => fn ($q) => $q->where('user_id', auth()->id())])
                ->get()
                ->map(function ($s) {
                    $s->my_vote = $s->votes->first();

                    return $s;
                });

            $myHm = HonorableMention::where('user_id', auth()->id())
                ->where('contest_id', $contest->id)
                ->with(['submission.images'])
                ->first();

            // The judge's own special-prize picks: [prize => Collection<submission>]
            $mySpecialPrizes = $contest->specialPrizes->mapWithKeys(function ($prize) use ($submissions) {
                $picked = SpecialPrizeVote::where('user_id', auth()->id())
                    ->where('special_prize_id', $prize->id)
                    ->pluck('submission_id');

                return [$prize->id => $submissions->whereIn('id', $picked)->values()];
            });

            return view('results.show', compact('contest', 'locked', 'submissions', 'myHm', 'mySpecialPrizes'));
        }

        // Full leaderboard for admin or closed contests
        $submissions = Submission::where('contest_id', $contest->id)
            ->with('images')
            ->withSum('votes', 'total_score')
            ->withCount('votes')
            ->get();

        $categories = $this->buildLeaderboard($submissions, $contest);

        // All honorable mentions for this contest, grouped by submission
        $honorableMentions = HonorableMention::where('contest_id', $contest->id)
            ->with(['user', 'submission.images'])
            ->get();

        $hmBySubmission = $honorableMentions->groupBy('submission_id');
        $hmConflicts = $hmBySubmission->filter(fn ($group) => $group->count() > 1);

        // Detect HMs that landed in the top 3 of any category
        $top3BySubmission = collect();
        foreach ($categories as $categoryName => $entries) {
            foreach ($entries->take(3) as $rank => $entry) {
                $top3BySubmission[$entry->id] = ['rank' => $rank + 1, 'category' => $categoryName];
            }
        }

        $hmWinnerConflicts = $hmBySubmission->filter(
            fn ($group, $submissionId) => $top3BySubmission->has($submissionId)
        )->map(fn ($group, $submissionId) => [
            'hms' => $group,
            'rank' => $top3BySubmission[$submissionId]['rank'],
            'category' => $top3BySubmission[$submissionId]['category'],
        ]);

        // Special prizes: per prize, submissions with ≥1 check, most checks first.
        $specialPrizeResults = $contest->specialPrizes->map(function ($prize) use ($contest) {
            $prize->setRelation('rankedSubmissions', Submission::where('contest_id', $contest->id)
                ->whereHas('specialPrizeVotes', fn ($q) => $q->where('special_prize_id', $prize->id))
                ->withCount(['specialPrizeVotes as prize_checks' => fn ($q) => $q->where('special_prize_id', $prize->id)])
                ->with('images')
                ->orderByDesc('prize_checks')
                ->orderBy('character_name')
                ->get());

            return $prize;
        });

        return view('results.show', compact(
            'contest', 'locked', 'categories',
            'honorableMentions', 'hmBySubmission', 'hmConflicts', 'hmWinnerConflicts',
            'specialPrizeResults'
        ));
    }

    private function buildLeaderboard(Collection $submissions, Contest $contest): array
    {
        $tiebreakCriteria = $contest->criteria()->tiebreakOrdered()->get();

        // Pre-aggregate every per-criterion score in one query instead of two
        // queries per submission per tiebreak level inside resolveTie().
        $tiebreakScores = $this->tiebreakScoreMap($contest, $tiebreakCriteria);

        $grouped = $submissions->groupBy('category');
        $categories = [];

        foreach ($grouped as $categoryName => $entries) {
            $ranked = $entries
                ->sortByDesc('votes_sum_total_score')
                ->values();

            $ranked = $this->applyTieBreakers($ranked, $tiebreakCriteria, $tiebreakScores);

            $categories[$categoryName] = $ranked;
        }

        ksort($categories);

        return $categories;
    }

    /**
     * [submission_id => [criterion_id => summed score]] for the tiebreak criteria.
     */
    private function tiebreakScoreMap(Contest $contest, Collection $tiebreakCriteria): Collection
    {
        if ($tiebreakCriteria->isEmpty()) {
            return collect();
        }

        return VoteScore::query()
            ->join('votes', 'votes.id', '=', 'vote_scores.vote_id')
            ->join('submissions', 'submissions.id', '=', 'votes.submission_id')
            ->where('submissions.contest_id', $contest->id)
            ->whereIn('vote_scores.contest_criterion_id', $tiebreakCriteria->pluck('id'))
            ->groupBy('votes.submission_id', 'vote_scores.contest_criterion_id')
            ->selectRaw('votes.submission_id, vote_scores.contest_criterion_id, SUM(vote_scores.score) as total')
            ->get()
            ->groupBy('submission_id')
            ->map(fn ($rows) => $rows->mapWithKeys(fn ($r) => [
                (int) $r->contest_criterion_id => (int) $r->total,
            ]));
    }

    private function applyTieBreakers(Collection $submissions, Collection $tiebreakCriteria, Collection $tiebreakScores): Collection
    {
        $result = collect();
        $i = 0;

        while ($i < $submissions->count()) {
            $current = $submissions[$i];
            $group = collect([$current]);

            $j = $i + 1;
            while ($j < $submissions->count()
                && $submissions[$j]->votes_sum_total_score === $current->votes_sum_total_score) {
                $group->push($submissions[$j]);
                $j++;
            }

            if ($group->count() > 1) {
                foreach ($this->resolveTie($group, $tiebreakCriteria, $tiebreakScores) as $s) {
                    $result->push($s);
                }
            } else {
                $result->push($current);
            }

            $i = $j;
        }

        return $result->values();
    }

    /**
     * Recursively narrows a tied group using the contest's ordered tiebreak
     * criteria. Falls back to marking the group as a Committee Vote once the
     * levels are exhausted (or none were configured).
     */
    private function resolveTie(Collection $group, Collection $tiebreakCriteria, Collection $tiebreakScores): Collection
    {
        if ($tiebreakCriteria->isEmpty()) {
            return $group->map(function ($s) {
                $s->committee_vote = true;

                return $s;
            });
        }

        $criterion = $tiebreakCriteria->first();
        $remaining = $tiebreakCriteria->slice(1)->values();
        $key = "tiebreak_score_{$criterion->id}";

        $scored = $group->map(function ($s) use ($criterion, $key, $tiebreakScores) {
            $s->{$key} = (int) ($tiebreakScores[$s->id][$criterion->id] ?? 0);

            return $s;
        });

        $maxScore = $scored->max($key);
        $winners = $scored->filter(fn ($s) => $s->{$key} === $maxScore)->values();
        $losers = $scored->filter(fn ($s) => $s->{$key} < $maxScore)->sortByDesc($key)->values();

        $resolvedWinners = $winners->count() > 1
            ? $this->resolveTie($winners, $remaining, $tiebreakScores)
            : $winners;

        return $resolvedWinners->concat($losers);
    }
}
