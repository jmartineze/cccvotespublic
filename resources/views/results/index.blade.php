@extends('layouts.app')

@section('title', 'Results')

@section('content')
<div class="px-4 py-5 max-w-2xl mx-auto">
    <div class="mb-6">
        <p class="section-label mb-1">Leaderboards</p>
        <h1 class="font-display text-2xl font-800" style="color: var(--color-text);">Results</h1>
    </div>

    @forelse($contests as $contest)
        <a href="{{ route('results.show', $contest) }}" class="contest-card block mb-3">
            @if($contest->cover_image_url)
                <img src="{{ $contest->cover_image_url }}" alt="{{ $contest->name }}" class="cover">
            @else
                <div class="cover-placeholder" style="aspect-ratio: 16/7;">
                    <svg class="w-8 h-8 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            @endif
            <div class="p-4 flex items-center justify-between gap-3">
                <div>
                    @if(auth()->user()->isJudge() && $contest->owner)
                        <span class="badge mb-1" style="background: rgba(155,90,255,0.12); color: #c4a0ff; border-color: rgba(155,90,255,0.25); font-size: 0.6rem;">{{ $contest->owner->name }}</span>
                    @endif
                    <h2 class="font-display font-700 text-base" style="color: var(--color-text);">{{ $contest->name }}</h2>
                    <p class="text-sm" style="color: var(--color-muted);">{{ $contest->submissions_count }} submissions</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="badge badge-{{ $contest->status === 'active' ? 'active' : 'closed' }}">{{ ucfirst($contest->status) }}</span>
                    <svg class="w-5 h-5" style="color: var(--color-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
        </a>
    @empty
        <div class="text-center py-16" style="color: var(--color-muted);">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <p class="font-display font-600">No results available yet</p>
        </div>
    @endforelse

    @if($contests->hasPages())
        <div class="mt-4">{{ $contests->links() }}</div>
    @endif
</div>
@endsection
