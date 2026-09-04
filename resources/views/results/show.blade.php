@extends('layouts.app')

@section('title', 'Results · ' . $contest->name)

@section('content')
<div class="px-4 py-5 max-w-2xl mx-auto">

    <div class="mb-5">
        <a href="{{ route('results.index') }}" class="inline-flex items-center gap-1.5 text-sm mb-3" style="color: var(--color-muted);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            All Results
        </a>
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-xl font-800" style="color: var(--color-text);">{{ $contest->name }}</h1>
                @if($locked)
                    <p class="text-sm" style="color: var(--color-muted);">Your personal scores · Leaderboard unlocks when voting closes</p>
                @else
                    <p class="text-sm" style="color: var(--color-muted);">Rankings by category · Sum of all judges' scores</p>
                @endif
            </div>
            <span class="badge badge-{{ $contest->status === 'active' ? 'active' : 'closed' }} flex-shrink-0">
                @if($contest->status === 'active') <span class="w-1.5 h-1.5 rounded-full bg-current animate-pulse"></span> @endif
                {{ ucfirst($contest->status) }}
            </span>
        </div>
    </div>

    {{-- ─── LOCKED STATE (contest still active, non-admin) ─── --}}
    @if($locked)
        {{-- Voting still open banner --}}
        <div class="rounded-2xl overflow-hidden mb-6" style="background: linear-gradient(135deg, rgba(155,90,255,0.12), rgba(255,45,120,0.08)); border: 1px solid rgba(155,90,255,0.2);">
            <div class="p-5 text-center">
                <div class="w-12 h-12 mx-auto mb-3 rounded-full flex items-center justify-center" style="background: rgba(155,90,255,0.2);">
                    <svg class="w-6 h-6" style="color: #c4a0ff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h2 class="font-display font-800 text-base mb-1" style="color: var(--color-text);">Leaderboard locked</h2>
                <p class="text-sm" style="color: var(--color-muted);">Results will be revealed once the contest closes. Below you can review your own votes.</p>
            </div>
        </div>

        {{-- Judge's own votes --}}
        @if($submissions->isEmpty())
            <p class="text-center py-8 text-sm" style="color: var(--color-muted);">You haven't cast any votes yet.</p>
        @else
            <p class="section-label mb-3">Your votes</p>
            <div class="space-y-2">
                @foreach($submissions as $submission)
                    @php $vote = $submission->my_vote; @endphp
                    <div class="card p-3 flex items-center gap-3">
                        {{-- Thumbnail --}}
                        @if($submission->images->isNotEmpty())
                            <img src="{{ $submission->images->first()->url }}" alt="{{ $submission->character_name }}"
                                class="w-12 h-12 rounded-lg object-cover flex-shrink-0">
                        @else
                            <div class="w-12 h-12 rounded-lg flex-shrink-0 flex items-center justify-center" style="background: var(--color-faint);">
                                <svg class="w-5 h-5 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        @endif

                        <div class="flex-1 min-w-0">
                            <p class="font-display font-700 text-sm truncate" style="color: var(--color-text);">{{ $submission->character_name }}</p>
                            <p class="text-xs" style="color: var(--color-muted);">{{ $submission->discord_user }}</p>
                            <p class="text-xs" style="color: var(--color-muted);">{{ $submission->category }} · {{ $submission->country }}</p>
                        </div>

                        @if($vote)
                            <div class="text-right flex-shrink-0">
                                <p class="font-mono font-700 text-base text-gradient-pink">{{ $vote->total_score }}</p>
                                <p class="text-xs" style="color: var(--color-muted);">/{{ $contest->criteria->sum('max_score') }}</p>
                            </div>
                        @else
                            <span class="badge badge-draft text-xs">Not voted</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Judge's Honorable Mention --}}
        <div class="mt-6">
            <p class="section-label mb-3">Your Honorable Mention</p>
            @if($myHm && $myHm->submission)
                @php $hmSub = $myHm->submission; @endphp
                <div class="card p-3 flex items-center gap-3" style="border-color: rgba(255,180,0,0.3); background: rgba(255,180,0,0.04);">
                    @if($hmSub->images->isNotEmpty())
                        <img src="{{ $hmSub->images->first()->url }}" alt="{{ $hmSub->character_name }}"
                            class="w-12 h-12 rounded-lg object-cover flex-shrink-0">
                    @else
                        <div class="w-12 h-12 rounded-lg flex-shrink-0 flex items-center justify-center" style="background: var(--color-faint);">
                            <svg class="w-5 h-5 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="font-display font-700 text-sm truncate" style="color: var(--color-text);">{{ $hmSub->character_name }}</p>
                        <p class="text-xs" style="color: var(--color-muted);">{{ $hmSub->discord_user }}</p>
                        <p class="text-xs" style="color: var(--color-muted);">{{ $hmSub->category }} · {{ $hmSub->country }}</p>
                    </div>
                    <span class="badge flex-shrink-0" style="background: rgba(255,180,0,0.15); color: #ffd700; border-color: rgba(255,180,0,0.3);">⭐ HM</span>
                </div>
            @else
                <div class="card p-4 text-center" style="border-style: dashed;">
                    <p class="text-sm" style="color: var(--color-muted);">You haven't selected an Honorable Mention yet.</p>
                </div>
            @endif
        </div>

        {{-- Judge's special-prize picks --}}
        @if(isset($mySpecialPrizes) && $contest->specialPrizes->isNotEmpty())
            <div class="mt-6">
                <p class="section-label mb-3">🏅 Your special-prize picks</p>
                <div class="space-y-3">
                    @foreach($contest->specialPrizes as $prize)
                        @php $picks = $mySpecialPrizes[$prize->id] ?? collect(); @endphp
                        <div class="card p-3">
                            <p class="font-display font-700 text-sm" style="color: var(--color-text);">{{ $prize->name }}</p>
                            @if($picks->isEmpty())
                                <p class="text-xs mt-1" style="color: var(--color-muted);">No submissions marked.</p>
                            @else
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @foreach($picks as $p)
                                        <span class="badge" style="background: rgba(0,212,255,0.12); color: #80e0ff; border-color: rgba(0,212,255,0.25);">{{ $p->character_name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    {{-- ─── FULL LEADERBOARD (admin or closed contest) ─── --}}
    @else
        @if(empty($categories))
            <div class="text-center py-16" style="color: var(--color-muted);">
                <p class="font-display font-600">No votes recorded yet</p>
            </div>
        @endif

        @foreach($categories as $categoryName => $entries)
            @php
                $maxScore = $entries->max('votes_sum_total_score') ?: 1;
            @endphp

            <div class="mb-8">
                <div class="category-header">
                    @php
                        [$gender, $style] = explode(' ', $categoryName, 2);
                        $icons = ['Male' => '♂', 'Female' => '♀', 'Trans' => '⚧'];
                        $styleColors = ['Anime' => '#c4a0ff', 'Realistic' => '#80e0ff'];
                    @endphp
                    <span style="color: var(--color-muted);">{{ $icons[$gender] ?? '' }}</span>
                    <span>{{ $categoryName }}</span>
                    <span class="badge" style="background: rgba(155,90,255,0.1); color: {{ $styleColors[$style] ?? '#fff' }}; border-color: rgba(155,90,255,0.2); font-size: 0.65rem;">
                        {{ $entries->count() }} entries
                    </span>
                </div>

                <div class="space-y-2">
                    @foreach($entries as $rank => $entry)
                        @php
                            $rankNum  = $rank + 1;
                            $score    = $entry->votes_sum_total_score ?? 0;
                            $votesCnt = $entry->votes_count ?? 0;
                            $barPct   = $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;
                            $isTop    = $rankNum <= 3;
                        @endphp

                        <div class="leaderboard-row {{ $isTop ? 'rank-top' : '' }}">
                            <div class="rank-badge rank-{{ $rankNum <= 3 ? $rankNum : 'n' }}">
                                {{ $rankNum <= 3 ? ['🥇','🥈','🥉'][$rankNum-1] : $rankNum }}
                            </div>

                            @if($entry->images->isNotEmpty())
                                <img src="{{ $entry->images->first()->url }}" alt="{{ $entry->character_name }}"
                                    class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                            @else
                                <div class="w-10 h-10 rounded-lg flex-shrink-0 flex items-center justify-center" style="background: var(--color-faint);">
                                    <svg class="w-5 h-5 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-display font-700 text-sm truncate" style="color: var(--color-text);">
                                        {{ $entry->character_name }}
                                    </p>
                                    @if(!empty($entry->committee_vote))
                                        <span class="badge badge-committee">⚡ Committee Vote</span>
                                    @endif
                                </div>
                                <p class="text-xs" style="color: var(--color-muted);">{{ $entry->discord_user }}</p>
                                <p class="text-xs mb-1.5" style="color: var(--color-muted);">{{ $entry->country }}</p>
                                <div class="score-bar">
                                    <div class="score-bar-fill" style="width: {{ $barPct }}%; background: {{ $isTop ? 'linear-gradient(90deg, #ff2d78, #9b5aff)' : 'var(--color-muted)' }};"></div>
                                </div>
                            </div>

                            <div class="text-right flex-shrink-0">
                                <div class="font-mono font-700 text-base {{ $isTop ? 'text-gradient-pink' : '' }}" style="{{ !$isTop ? 'color: var(--color-text)' : '' }}">
                                    {{ $score }}
                                </div>
                                @if($votesCnt > 0)
                                    <div class="text-xs" style="color: var(--color-faint);">{{ $votesCnt }} {{ Str::plural('judge', $votesCnt) }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Honorable Mentions section --}}
        @if($honorableMentions->isNotEmpty())
            <div class="mt-6 mb-4">
                <div class="flex items-center gap-2 mb-3">
                    <p class="section-label">⭐ Honorable Mentions</p>
                    <span class="badge" style="background: rgba(255,180,0,0.12); color: #ffd700; border-color: rgba(255,180,0,0.25); font-size: 0.65rem;">{{ $honorableMentions->count() }} {{ Str::plural('pick', $honorableMentions->count()) }}</span>
                </div>

                {{-- Duplicate-judge conflict alert --}}
                @if($hmConflicts->isNotEmpty())
                    <div class="p-4 rounded-xl mb-3" style="background: rgba(255,80,0,0.08); border: 1px solid rgba(255,80,0,0.3);">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" style="color: #ff6030;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                            <div>
                                <p class="font-display font-700 text-sm mb-1" style="color: #ff6030;">Duplicate pick</p>
                                <p class="text-xs" style="color: var(--color-muted);">
                                    {{ $hmConflicts->count() }} {{ Str::plural('submission', $hmConflicts->count()) }}
                                    {{ $hmConflicts->count() === 1 ? 'was' : 'were' }} chosen by more than one judge.
                                    The judges involved should coordinate and pick different submissions.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Winner-is-HM conflict alert --}}
                @if($hmWinnerConflicts->isNotEmpty())
                    <div class="p-4 rounded-xl mb-3" style="background: rgba(255,80,0,0.08); border: 1px solid rgba(255,80,0,0.3);">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" style="color: #ff6030;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                            <div>
                                <p class="font-display font-700 text-sm mb-1" style="color: #ff6030;">Winner marked as HM</p>
                                <p class="text-xs" style="color: var(--color-muted);">
                                    {{ $hmWinnerConflicts->count() }} {{ Str::plural('submission', $hmWinnerConflicts->count()) }}
                                    already placed in the top 3 of {{ $hmWinnerConflicts->count() === 1 ? 'a category' : 'their categories' }}
                                    and cannot be an Honorable Mention. The judges who picked
                                    {{ $hmWinnerConflicts->count() === 1 ? 'it' : 'them' }} should choose a different submission.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="space-y-2">
                    @foreach($hmBySubmission as $submissionId => $group)
                        @php
                            $hmSub         = $group->first()->submission;
                            $isDuplicate   = $group->count() > 1;
                            $isWinner      = $hmWinnerConflicts->has($submissionId);
                            $hasConflict   = $isDuplicate || $isWinner;
                            $judges        = $group->map(fn($hm) => $hm->user->name)->join(', ');
                            $winnerInfo    = $isWinner ? $hmWinnerConflicts[$submissionId] : null;
                            $rankLabels    = [1 => '🥇 1st', 2 => '🥈 2nd', 3 => '🥉 3rd'];
                        @endphp
                        @if($hmSub)
                            <div class="card p-3 flex items-center gap-3"
                                style="{{ $hasConflict ? 'border-color: rgba(255,80,0,0.4); background: rgba(255,80,0,0.04);' : 'border-color: rgba(255,180,0,0.25); background: rgba(255,180,0,0.03);' }}">
                                @if($hmSub->images->isNotEmpty())
                                    <img src="{{ $hmSub->images->first()->url }}" alt="{{ $hmSub->character_name }}"
                                        class="w-12 h-12 rounded-lg object-cover flex-shrink-0">
                                @else
                                    <div class="w-12 h-12 rounded-lg flex-shrink-0 flex items-center justify-center" style="background: var(--color-faint);">
                                        <svg class="w-5 h-5 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <p class="font-display font-700 text-sm truncate" style="color: var(--color-text);">{{ $hmSub->character_name }}</p>
                                        @if($isDuplicate)
                                            <span class="badge flex-shrink-0" style="background: rgba(255,80,0,0.15); color: #ff6030; border-color: rgba(255,80,0,0.3); font-size: 0.6rem;">⚠ Duplicate</span>
                                        @endif
                                        @if($isWinner)
                                            <span class="badge flex-shrink-0" style="background: rgba(255,80,0,0.15); color: #ff6030; border-color: rgba(255,80,0,0.3); font-size: 0.6rem;">
                                                ⚠ {{ $rankLabels[$winnerInfo['rank']] ?? $winnerInfo['rank'].'th' }} place
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs" style="color: var(--color-muted);">{{ $hmSub->discord_user }} · {{ $hmSub->country }}</p>
                                    @if($isWinner)
                                        <p class="text-xs mt-0.5" style="color: #ff9060;">Winner in {{ $winnerInfo['category'] }}</p>
                                    @endif
                                    <p class="text-xs mt-0.5" style="color: {{ $hasConflict ? '#ff9060' : 'rgba(255,180,0,0.7)' }};">
                                        by {{ $judges }}
                                    </p>
                                </div>
                                <span class="badge flex-shrink-0" style="background: rgba(255,180,0,0.15); color: #ffd700; border-color: rgba(255,180,0,0.3);">⭐</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        @if(collect($categories)->flatten(1)->contains('committee_vote', true))
            <div class="card-glass p-4 mt-2">
                <p class="section-label mb-2">Tie-breaker legend</p>
                <div class="flex items-start gap-3">
                    <span class="badge badge-committee">⚡ Committee Vote</span>
                    <p class="text-sm" style="color: var(--color-muted);">Tied on total score and all configured tiebreak criteria. A manual committee decision is required.</p>
                </div>
            </div>
        @endif

        {{-- Special prizes — ranked by number of judge checks --}}
        @if(isset($specialPrizeResults) && $specialPrizeResults->isNotEmpty())
            <div class="divider"></div>
            <div class="flex items-center gap-2 mb-4">
                <p class="section-label">🏅 Special Prizes</p>
            </div>

            @foreach($specialPrizeResults as $prize)
                <div class="mb-6">
                    <h3 class="font-display font-800 text-base" style="color: var(--color-text);">{{ $prize->name }}</h3>
                    @if($prize->description)
                        <p class="text-xs mb-3" style="color: var(--color-muted);">{{ $prize->description }}</p>
                    @else
                        <div class="mb-3"></div>
                    @endif

                    @if($prize->rankedSubmissions->isEmpty())
                        <p class="text-sm" style="color: var(--color-muted);">No submissions marked for this prize.</p>
                    @else
                        <div class="space-y-2">
                            @foreach($prize->rankedSubmissions as $i => $sub)
                                <div class="leaderboard-row {{ $i === 0 ? 'rank-top' : '' }}">
                                    <span class="font-mono font-700 text-sm w-6 flex-shrink-0" style="color: var(--color-muted);">{{ $i + 1 }}</span>
                                    @if($sub->images->isNotEmpty())
                                        <img src="{{ $sub->images->first()->url }}" alt="{{ $sub->character_name }}" class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                                    @else
                                        <div class="w-10 h-10 rounded-lg flex-shrink-0" style="background: var(--color-faint);"></div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="font-display font-700 text-sm truncate" style="color: var(--color-text);">{{ $sub->character_name }}</p>
                                        <p class="text-xs" style="color: var(--color-muted);">{{ $sub->discord_user }}</p>
                                    </div>
                                    <span class="badge flex-shrink-0" style="background: rgba(0,212,255,0.12); color: #80e0ff; border-color: rgba(0,212,255,0.25);">
                                        {{ $sub->prize_checks }} {{ Str::plural('check', $sub->prize_checks) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    @endif

</div>
@endsection
