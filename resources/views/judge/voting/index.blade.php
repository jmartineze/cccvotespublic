@extends('layouts.app')

@section('title', 'Vote · ' . $contest->name)

@section('content')
<div class="px-4 py-5 max-w-2xl mx-auto">

    {{-- Contest header --}}
    <div class="mb-5">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-sm mb-3" style="color: var(--color-muted);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            All Contests
        </a>
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-xl font-800" style="color: var(--color-text);">{{ $contest->name }}</h1>
                <p class="text-sm" style="color: var(--color-muted);">{{ $totalSubmissions }} submissions</p>
            </div>
            {{-- Progress ring --}}
            <div class="flex-shrink-0 relative" title="{{ $votedCount }}/{{ $totalSubmissions }} voted">
                @php $pct = $totalSubmissions > 0 ? ($votedCount / $totalSubmissions) : 0; $r = 20; $circ = 2 * pi() * $r; @endphp
                <svg width="52" height="52" class="progress-ring">
                    <circle class="progress-ring-track" cx="26" cy="26" r="{{ $r }}"/>
                    <circle class="progress-ring-fill" cx="26" cy="26" r="{{ $r }}"
                        stroke-dasharray="{{ $circ }}"
                        stroke-dashoffset="{{ $circ * (1 - $pct) }}"/>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="font-mono text-xs font-700" style="color: var(--color-text);">{{ $votedCount }}</span>
                    <span class="font-mono text-xs" style="color: var(--color-muted);">/{{ $totalSubmissions }}</span>
                </div>
            </div>
        </div>

        @if($contest->isClosed())
            <div class="alert alert-error mt-3">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                This contest is closed. Votes can no longer be cast.
            </div>
        @endif
    </div>

    @if($submissions->isEmpty())
        <div class="text-center py-16" style="color: var(--color-muted);">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="font-display font-600">No submissions yet</p>
        </div>
    @else
        @php
            $categoryList = $submissions->groupBy('category')->keys()->sort()->values();
        @endphp

        @php $unvotedCount = $submissions->where('has_voted', false)->count(); @endphp

        <div x-data="{ active: 'all', search: '' }">

            {{-- Search --}}
            <div class="relative mb-3">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color: var(--color-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
                <input type="text" x-model="search"
                    placeholder="Search by character or creator..."
                    class="input pl-9" style="padding-left: 2.25rem;">
            </div>

            {{-- Category filter pills --}}
            <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 mb-4" style="scrollbar-width: none;">
                <button @click="active = 'all'"
                    :class="active === 'all' ? 'btn-primary' : 'btn-secondary'"
                    class="btn btn-sm flex-shrink-0">All</button>
                <button @click="active = 'unvoted'"
                    :class="active === 'unvoted' ? 'btn-primary' : 'btn-secondary'"
                    class="btn btn-sm flex-shrink-0">
                    Pending
                    @if($unvotedCount > 0)
                        <span class="ml-1 inline-flex items-center justify-center rounded-full font-mono text-xs"
                            style="background: rgba(255,45,120,0.25); color: #ff2d78; min-width: 1.1rem; padding: 0 0.25rem;">{{ $unvotedCount }}</span>
                    @endif
                </button>
                @if($categoryList->count() > 1)
                    @foreach($categoryList as $cat)
                        <button @click="active = '{{ $cat }}'"
                            :class="active === '{{ $cat }}' ? 'btn-primary' : 'btn-secondary'"
                            class="btn btn-sm flex-shrink-0">{{ $cat }}</button>
                    @endforeach
                @endif
            </div>

            {{-- Flat 2-column flex grid — each item is an explicit 50%-wide wrapper --}}
            <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                @foreach($submissions as $submission)
                    @php $voted = $submission->has_voted ? 'true' : 'false'; @endphp
                    @php
                        $charName    = addslashes(mb_strtolower($submission->character_name));
                        $discordUser = addslashes(mb_strtolower($submission->discord_user));
                    @endphp
                    <div
                        x-show="(active === 'all' || (active === 'unvoted' && !{{ $voted }}) || (active !== 'all' && active !== 'unvoted' && active === '{{ $submission->category }}')) && (search === '' || '{{ $charName }}'.includes(search.toLowerCase()) || '{{ $discordUser }}'.includes(search.toLowerCase()))"
                        style="width: calc(50% - 6px); min-width: 0;"
                    >
                        @include('partials.submission-card', ['submission' => $submission, 'contest' => $contest])
                    </div>
                @endforeach
            </div>

        </div>
    @endif

</div>
@endsection
