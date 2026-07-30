<?php

namespace App\Http\Controllers\Judge;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVoteRequest;
use App\Models\Contest;
use App\Models\HonorableMention;
use App\Models\Submission;
use App\Models\Vote;
use App\Models\VoteScore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VotingController extends Controller
{
    public function index(Contest $contest): View
    {
        abort_if(auth()->user()->isAnyAdmin(), 403, 'Admins cannot participate in voting.');

        $myHm = (int) HonorableMention::where('user_id', auth()->id())
            ->where('contest_id', $contest->id)
            ->value('submission_id');

        $submissions = $contest->submissions()
            ->with(['images', 'votes' => fn ($q) => $q->where('user_id', auth()->id())])
            ->get()
            ->map(function ($submission) use ($myHm) {
                $submission->my_vote = $submission->votes->first();
                $submission->has_voted = ! is_null($submission->my_vote);
                $submission->images_count = $submission->images->count();
                $submission->is_my_hm = $submission->id === $myHm;

                return $submission;
            })
            ->sortBy('has_voted')
            ->values();

        $totalSubmissions = $submissions->count();
        $votedCount = $submissions->where('has_voted', true)->count();

        return view('judge.voting.index', compact('contest', 'submissions', 'totalSubmissions', 'votedCount', 'myHm'));
    }

    public function show(Contest $contest, Submission $submission): View
    {
        abort_if(auth()->user()->isAnyAdmin(), 403, 'Admins cannot participate in voting.');
        abort_if($submission->contest_id !== $contest->id, 404);

        $submission->load('images');
        $myVote = $submission->getVoteFrom(auth()->id());

        $criteria = $contest->criteria;

        $myVoteScores = $myVote
            ? $myVote->voteScores()->pluck('score', 'contest_criterion_id')
            : collect();

        $myHm = (int) HonorableMention::where('user_id', auth()->id())
            ->where('contest_id', $contest->id)
            ->value('submission_id');

        $isMyHm = $myHm === $submission->id;

        return view('judge.voting.show', compact('contest', 'submission', 'myVote', 'myHm', 'isMyHm', 'criteria', 'myVoteScores'));
    }

    public function vote(StoreVoteRequest $request, Contest $contest, Submission $submission): RedirectResponse
    {
        abort_if(auth()->user()->isAnyAdmin(), 403, 'Admins cannot participate in voting.');
        abort_if($submission->contest_id !== $contest->id, 404);
        abort_if($contest->isClosed(), 403, 'This contest is closed for voting.');

        DB::transaction(function () use ($request, $submission) {
            $vote = Vote::updateOrCreate(
                ['user_id' => auth()->id(), 'submission_id' => $submission->id],
                ['comment' => $request->validated('comment'), 'total_score' => 0]
            );

            foreach ($request->validated('scores') as $criterionId => $score) {
                VoteScore::updateOrCreate(
                    ['vote_id' => $vote->id, 'contest_criterion_id' => $criterionId],
                    ['score' => $score]
                );
            }

            $vote->recalculateTotalScore();
        });

        return redirect()
            ->route('judge.voting.index', $contest)
            ->with('success', "Vote cast for {$submission->character_name}!");
    }

    public function honorableMention(Contest $contest, Submission $submission): RedirectResponse
    {
        abort_if(auth()->user()->isAnyAdmin(), 403, 'Admins cannot participate in voting.');
        abort_if($submission->contest_id !== $contest->id, 404);

        $existing = HonorableMention::where('user_id', auth()->id())
            ->where('contest_id', $contest->id)
            ->first();

        // Toggle off if same submission is picked again
        if ($existing && $existing->submission_id === $submission->id) {
            $existing->delete();

            return back()->with('success', 'Honorable Mention removed.');
        }

        HonorableMention::updateOrCreate(
            ['user_id' => auth()->id(), 'contest_id' => $contest->id],
            ['submission_id' => $submission->id]
        );

        return back()->with('success', "⭐ {$submission->character_name} marked as your Honorable Mention!");
    }
}
