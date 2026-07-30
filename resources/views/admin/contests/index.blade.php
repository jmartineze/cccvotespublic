@extends('layouts.app')

@section('title', 'Admin · Contests')

@section('content')
<div class="px-4 py-5 max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <p class="section-label mb-1">Admin</p>
            <h1 class="font-display text-xl font-800" style="color: var(--color-text);">Contests</h1>
        </div>
        <a href="{{ route('admin.contests.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New
        </a>
    </div>

    {{-- Quick links --}}
    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.5rem;" class="mb-6">
        <a href="{{ route('admin.submissions.index') }}" class="card p-3 flex items-center gap-2 hover:border-violet-500/30 transition-colors">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(155,90,255,0.15);">
                <svg class="w-4 h-4" style="color: #c4a0ff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="font-display font-700 text-sm" style="color: var(--color-text);">Submissions</span>
        </a>
        <a href="{{ route('admin.submissions.create') }}" class="card p-3 flex items-center gap-2 hover:border-pink-500/30 transition-colors">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(255,45,120,0.15);">
                <svg class="w-4 h-4" style="color: #ff80a8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <span class="font-display font-700 text-sm" style="color: var(--color-text);">Upload</span>
        </a>
        <a href="{{ route('admin.users.index') }}" class="card p-3 flex items-center gap-2 hover:border-cyan-500/30 transition-colors">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(0,212,255,0.1);">
                <svg class="w-4 h-4" style="color: #80e0ff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <span class="font-display font-700 text-sm" style="color: var(--color-text);">Judges</span>
        </a>
        <a href="{{ route('admin.contests.create') }}" class="card p-3 flex items-center gap-2 hover:border-emerald-500/30 transition-colors">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(0,224,160,0.1);">
                <svg class="w-4 h-4" style="color: #00e0a0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <span class="font-display font-700 text-sm" style="color: var(--color-text);">New Contest</span>
        </a>
    </div>

    <div class="space-y-3">
        @forelse($contests as $contest)
            @php $badge = $contest->status_badge; @endphp
            <div class="card p-4">
                <div class="flex items-start gap-3">
                    {{-- Cover thumb --}}
                    @if($contest->cover_image_url)
                        <img src="{{ $contest->cover_image_url }}" alt="" class="w-14 h-14 rounded-lg object-cover flex-shrink-0">
                    @else
                        <div class="w-14 h-14 rounded-lg flex-shrink-0 flex items-center justify-center" style="background: linear-gradient(135deg, #1a1430, #0e1420);">
                            <svg class="w-6 h-6 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="font-display font-700 text-sm truncate" style="color: var(--color-text);">{{ $contest->name }}</h2>
                            <span class="badge badge-{{ $contest->status }}">{{ $badge['label'] }}</span>
                        </div>
                        <p class="text-xs mt-0.5" style="color: var(--color-muted);">
                            <span class="font-mono font-700" style="color: var(--color-text);">{{ $contest->submissions_count }}</span> submissions
                        </p>
                        @if($contest->description)
                            <p class="text-xs mt-1 line-clamp-1" style="color: var(--color-muted);">{{ $contest->description }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2 mt-3 pt-3" style="border-top: 1px solid var(--color-border);">
                    <a href="{{ route('admin.contests.edit', $contest) }}" class="btn btn-secondary btn-sm flex-1 justify-center">Edit</a>
                    @if($contest->status !== 'draft')
                        <a href="{{ route('results.show', $contest) }}" class="btn btn-ghost btn-sm">Results</a>
                    @endif
                    <form method="POST" action="{{ route('admin.contests.destroy', $contest) }}"
                          onsubmit="return confirm('Delete this contest and all its submissions?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-center py-16" style="color: var(--color-muted);">
                <p class="font-display font-600">No contests yet</p>
                <a href="{{ route('admin.contests.create') }}" class="btn btn-primary btn-sm mt-3">Create first contest</a>
            </div>
        @endforelse
    </div>
</div>
@endsection
