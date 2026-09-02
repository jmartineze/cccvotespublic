@extends('layouts.app')

@section('title', $submission->character_name . ' · Vote')

@section('content')
@php
    $images = $submission->images;
    $videoIndices = $images->filter(fn($img) => $img->isVideo())->keys()->values()->toArray();
@endphp
<div class="max-w-2xl mx-auto" x-data="votingPanel(@js($videoIndices))">

    {{-- Back nav --}}
    <div class="px-4 pt-4 pb-2">
        <a href="{{ route('judge.voting.index', $contest) }}" class="inline-flex items-center gap-1.5 text-sm" style="color: var(--color-muted);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ $contest->name }}
        </a>
    </div>

    {{-- Image / Video Carousel --}}
    @if($images->isNotEmpty())
        <div class="carousel-container mx-0"
             x-on:touchstart="touchStart($event)"
             x-on:touchend="touchEnd($event)">
            <div class="carousel-track" :style="`transform: translateX(-${current * 100}%)`">
                @foreach($images as $image)
                    <div class="carousel-slide" data-slide-index="{{ $loop->index }}">
                        @if($image->isVideo())
                            <video src="{{ $image->url }}"
                                   loop muted playsinline
                                   data-carousel-video
                                   x-on:mouseenter="$el.play()"
                                   x-on:mouseleave="if (current !== {{ $loop->index }}) $el.pause()"></video>
                        @else
                            <img src="{{ $image->url }}" alt="{{ $submission->character_name }} — image {{ $loop->iteration }}" class="">
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Prev/next arrows (tablet+) --}}
            @if($images->count() > 1)
                <button @click="prev()" class="absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full flex items-center justify-center" style="background: rgba(0,0,0,0.6); color: white;" :class="current === 0 ? 'opacity-30 pointer-events-none' : ''">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button @click="next({{ $images->count() }})" class="absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full flex items-center justify-center" style="background: rgba(0,0,0,0.6); color: white;" :class="current === {{ $images->count() - 1 }} ? 'opacity-30 pointer-events-none' : ''">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>

                {{-- Dot indicators --}}
                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
                    @foreach($images as $img)
                        <button @click="current = {{ $loop->index }}" class="carousel-dot" :class="current === {{ $loop->index }} ? 'active' : ''"></button>
                    @endforeach
                </div>

                {{-- Counter --}}
                <div class="absolute top-3 right-3 px-2 py-0.5 rounded-full text-xs font-mono" style="background: rgba(0,0,0,0.6); color: white;">
                    <span x-text="current + 1"></span>/{{ $images->count() }}
                </div>
            @endif
        </div>
    @else
        <div style="background: linear-gradient(160deg, #1a1428, #0e1428); height: 280px;" class="flex items-center justify-center">
            <svg class="w-12 h-12 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
    @endif

    {{-- Submission info --}}
    <div class="px-4 pt-4 pb-2">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="font-display text-xl font-800" style="color: var(--color-text);">{{ $submission->character_name }}</h1>
                <p class="text-sm font-mono mt-0.5" style="color: #00d4ff;">{{ '@'.$submission->discord_user }}</p>
                <div class="flex items-center gap-2 mt-1 flex-wrap">
                    <span class="text-sm" style="color: var(--color-muted);">{{ $submission->country }}</span>
                    <span class="badge" style="background: rgba(155,90,255,0.12); color: #c4a0ff; border-color: rgba(155,90,255,0.2);">{{ $submission->gender }}</span>
                    <span class="badge" style="background: rgba(0,212,255,0.08); color: #80e0ff; border-color: rgba(0,212,255,0.2);">{{ $submission->style }}</span>
                </div>
            </div>
            @if($myVote)
                <span class="badge badge-active flex-shrink-0">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Voted
                </span>
            @endif
        </div>

        {{-- Backstory --}}
        <div class="mt-4 p-4 rounded-xl" style="background: rgba(255,255,255,0.025); border: 1px solid var(--color-border);">
            <p class="section-label mb-2">Backstory</p>
            <p class="text-sm leading-relaxed" style="color: var(--color-text);">{!! nl2br(e($submission->backstory)) !!}</p>
        </div>

        @if($submission->scenario_description)
            <div class="mt-3 p-4 rounded-xl" style="background: rgba(255,255,255,0.025); border: 1px solid var(--color-border);">
                <p class="section-label mb-2">Scenario</p>
                <p class="text-sm leading-relaxed" style="color: var(--color-text);">{!! nl2br(e($submission->scenario_description)) !!}</p>
            </div>
        @endif
    </div>

    {{-- Voting form --}}
    <div class="px-4 pt-2 pb-6">
        @if($contest->isClosed())
            <div class="alert alert-error">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                This contest is closed for voting.
            </div>

            {{-- Read-only scores --}}
            @if($myVote)
                @php $criteriaColors = ['#ff2d78', '#00d4ff', '#9b5aff', '#00c070']; @endphp
                <div class="divider"></div>
                <h2 class="font-display font-700 text-base mb-4" style="color: var(--color-text);">Your scores</h2>
                <div class="space-y-4">
                    @foreach($criteria as $criterion)
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-display font-700 text-sm" style="color: var(--color-text);">{{ $criterion->name }}</p>
                                @if($criterion->description)
                                    <p class="text-xs" style="color: var(--color-muted);">{{ $criterion->description }}</p>
                                @endif
                            </div>
                            <div class="flex items-baseline gap-1 flex-shrink-0">
                                <span class="font-mono font-700 text-xl" style="color: {{ $criterion->color ?? $criteriaColors[$loop->index % count($criteriaColors)] }};">{{ $myVoteScores[$criterion->id] ?? 0 }}</span>
                                <span class="text-xs" style="color: var(--color-muted);">/{{ $criterion->max_score }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($myVote->comment)
                    <div class="mt-4 p-4 rounded-xl" style="background: rgba(255,255,255,0.02); border: 1px solid var(--color-border);">
                        <p class="section-label mb-2">Your comment</p>
                        <p class="text-sm leading-relaxed" style="color: var(--color-text);">{!! nl2br(e($myVote->comment)) !!}</p>
                    </div>
                @endif

                <div class="mt-4 p-4 rounded-xl text-center" style="background: rgba(255,255,255,0.02); border: 1px solid var(--color-border);">
                    <p class="section-label mb-1">Your total score</p>
                    <div class="flex items-baseline justify-center gap-1">
                        <span class="font-display font-800 text-4xl text-gradient-pink">{{ $myVote->total_score }}</span>
                        <span class="font-display font-600 text-lg" style="color: var(--color-muted);">/{{ $criteria->sum('max_score') }}</span>
                    </div>
                </div>
            @endif

            {{-- HM (editable even when closed, to resolve conflicts) --}}
            <div class="mt-3 p-4 rounded-xl" style="background: rgba(255,180,0,0.05); border: 1px solid rgba(255,180,0,0.15);">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div>
                        <p class="font-display font-700 text-sm" style="color: var(--color-text);">⭐ Honorable Mention</p>
                        <p class="text-xs mt-0.5" style="color: var(--color-muted);">
                            @if($isMyHm)
                                This is your current Honorable Mention for this contest. Tap to remove it.
                            @elseif($myHm)
                                You already have an Honorable Mention. Selecting this one will replace it.
                            @else
                                Choose one submission per contest that stands out beyond the scores.
                            @endif
                        </p>
                    </div>
                    @if($isMyHm)
                        <span class="badge flex-shrink-0" style="background: rgba(255,180,0,0.15); color: #ffd700; border-color: rgba(255,180,0,0.3);">Active</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('judge.voting.hm', [$contest, $submission]) }}">
                    @csrf
                    <button type="submit" class="btn btn-full"
                        style="{{ $isMyHm
                            ? 'background: rgba(255,180,0,0.15); color: #ffd700; border: 1px solid rgba(255,180,0,0.35);'
                            : 'background: rgba(255,180,0,0.08); color: rgba(255,180,0,0.7); border: 1px solid rgba(255,180,0,0.2);' }}">
                        <span style="font-size: 1rem;">⭐</span>
                        {{ $isMyHm ? 'Remove Honorable Mention' : ($myHm ? 'Move Honorable Mention here' : 'Mark as Honorable Mention') }}
                    </button>
                </form>
            </div>
        @else
            <div class="divider"></div>
            <h2 class="font-display font-700 text-base mb-1" style="color: var(--color-text);">
                {{ $myVote ? 'Update your vote' : 'Cast your vote' }}
            </h2>
            <p class="text-xs mb-4" style="color: var(--color-muted);">Scores are private. Other judges cannot see your ratings.</p>

            @if($errors->any())
                <div class="alert alert-error mb-4">
                    {{ $errors->first() }}
                </div>
            @endif

            @php $criteriaColors = ['#ff2d78', '#00d4ff', '#9b5aff', '#00c070']; @endphp
            <form method="POST" action="{{ route('judge.voting.vote', [$contest, $submission]) }}" class="space-y-6">
                @csrf

                @foreach($criteria as $criterion)
                    @php
                        $color = $criterion->color ?? $criteriaColors[$loop->index % count($criteriaColors)];
                        $default = intdiv($criterion->max_score, 2);
                        $sliderClass = match($loop->index % 4) {
                            0 => 'slider-pink',
                            1 => 'slider-cyan',
                            2 => 'slider-violet',
                            default => 'slider-emerald',
                        };
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="font-display font-700 text-sm" style="color: var(--color-text);">{{ $criterion->name }}</p>
                                @if($criterion->description)
                                    <p class="text-xs" style="color: var(--color-muted);">{{ $criterion->description }}</p>
                                @endif
                            </div>
                            <div class="flex items-baseline gap-1">
                                <span class="score-display" x-text="scores[{{ $criterion->id }}]" style="font-size: 1.5rem; color: {{ $color }};"></span>
                                <span class="text-xs" style="color: var(--color-muted);">/{{ $criterion->max_score }}</span>
                            </div>
                        </div>
                        <input type="range" name="scores[{{ $criterion->id }}]" min="0" max="{{ $criterion->max_score }}" step="1"
                            x-model="scores[{{ $criterion->id }}]"
                            value="{{ old('scores.'.$criterion->id, $myVoteScores[$criterion->id] ?? $default) }}"
                            class="{{ $sliderClass }}">
                        <div class="flex justify-between text-xs mt-1" style="color: var(--color-muted);">
                            <span>0</span><span>{{ intdiv($criterion->max_score, 2) }}</span><span>{{ $criterion->max_score }}</span>
                        </div>
                    </div>
                @endforeach

                {{-- Comment --}}
                <div>
                    <label class="section-label block mb-1.5">
                        Comment
                        @if($contest->isCharacterScenario())
                            <span style="color: #ff2d78;">*</span>
                        @else
                            <span style="color: var(--color-muted); text-transform: none; font-weight: 400;">(optional)</span>
                        @endif
                    </label>
                    <textarea name="comment" rows="3" class="input {{ $errors->has('comment') ? 'input-error' : '' }}"
                        placeholder="Explain your reasoning..."
                        @if($contest->isCharacterScenario()) required @endif
                    >{{ old('comment', $myVote?->comment) }}</textarea>
                    @error('comment') <p class="error-msg">{{ $message }}</p> @enderror
                </div>

                {{-- Total preview --}}
                <div class="p-4 rounded-xl text-center" style="background: rgba(255,255,255,0.02); border: 1px solid var(--color-border);">
                    <p class="section-label mb-1">Your total score</p>
                    <div class="flex items-baseline justify-center gap-1">
                        <span class="font-display font-800 text-4xl text-gradient-pink" x-text="total"></span>
                        <span class="font-display font-600 text-lg" style="color: var(--color-muted);">/{{ $criteria->sum('max_score') }}</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    {{ $myVote ? 'Update Vote' : 'Submit Vote' }}
                </button>
            </form>

            {{-- Honorable Mention --}}
            <div class="divider"></div>
            <div class="p-4 rounded-xl" style="background: rgba(255,180,0,0.05); border: 1px solid rgba(255,180,0,0.15);">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div>
                        <p class="font-display font-700 text-sm" style="color: var(--color-text);">⭐ Honorable Mention</p>
                        <p class="text-xs mt-0.5" style="color: var(--color-muted);">
                            @if($isMyHm)
                                This is your current Honorable Mention for this contest. Tap to remove it.
                            @elseif($myHm)
                                You already have an Honorable Mention. Selecting this one will replace it.
                            @else
                                Choose one submission per contest that stands out beyond the scores.
                            @endif
                        </p>
                    </div>
                    @if($isMyHm)
                        <span class="badge flex-shrink-0" style="background: rgba(255,180,0,0.15); color: #ffd700; border-color: rgba(255,180,0,0.3);">Active</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('judge.voting.hm', [$contest, $submission]) }}">
                    @csrf
                    <button type="submit" class="btn btn-full"
                        style="{{ $isMyHm
                            ? 'background: rgba(255,180,0,0.15); color: #ffd700; border: 1px solid rgba(255,180,0,0.35);'
                            : 'background: rgba(255,180,0,0.08); color: rgba(255,180,0,0.7); border: 1px solid rgba(255,180,0,0.2);' }}">
                        <span style="font-size: 1rem;">⭐</span>
                        {{ $isMyHm ? 'Remove Honorable Mention' : ($myHm ? 'Move Honorable Mention here' : 'Mark as Honorable Mention') }}
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>

<script>
function votingPanel(videoIndices) {
    return {
        current: 0,
        touchStartX: 0,
        videoIndices: videoIndices || [],
        scores: {!! json_encode($criteria->mapWithKeys(fn ($c) => [
            (string) $c->id => (int) old("scores.{$c->id}", $myVoteScores[$c->id] ?? intdiv($c->max_score, 2)),
        ])) !!},
        get total() {
            return Object.values(this.scores).reduce((sum, v) => sum + Number(v), 0);
        },
        init() {
            this.$watch('current', val => this.$nextTick(() => this.handleVideoAutoplay(val)));
            this.$nextTick(() => this.handleVideoAutoplay(0));
        },
        handleVideoAutoplay(index) {
            document.querySelectorAll('[data-carousel-video]').forEach(v => v.pause());
            if (this.videoIndices.includes(index)) {
                const slide = document.querySelector(`[data-slide-index="${index}"]`);
                if (slide) {
                    const video = slide.querySelector('[data-carousel-video]');
                    if (video) video.play().catch(() => {});
                }
            }
        },
        prev() { if (this.current > 0) this.current--; },
        next(max) { if (this.current < max - 1) this.current++; },
        touchStart(e) { this.touchStartX = e.touches[0].clientX; },
        touchEnd(e) {
            const diff = this.touchStartX - e.changedTouches[0].clientX;
            const total = {{ $images->count() }};
            if (Math.abs(diff) > 40) {
                if (diff > 0) this.next(total);
                else this.prev();
            }
        },
    };
}
</script>
@endsection
