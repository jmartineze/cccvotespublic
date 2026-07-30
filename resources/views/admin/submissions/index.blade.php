@extends('layouts.app')

@section('title', 'Admin · Submissions')

@section('content')
<div class="px-4 py-5 max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <p class="section-label mb-1">Admin</p>
            <h1 class="font-display text-xl font-800" style="color: var(--color-text);">Submissions</h1>
        </div>
        <a href="{{ route('admin.submissions.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Upload
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="mb-4 flex flex-col gap-2" x-data>
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color: var(--color-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Search by character or creator..."
                class="input pl-9"
                style="padding-left: 2.25rem;"
                x-init="$nextTick(() => { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length) })"
                @input.debounce.400ms="$el.form.submit()">
        </div>
        <select name="contest_id" class="input" onchange="this.form.submit()">
            <option value="">All contests</option>
            @foreach($contests as $c)
                <option value="{{ $c->id }}" {{ request('contest_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
    </form>

    <div class="space-y-2">
        @forelse($submissions as $submission)
            <div class="card p-3 flex items-center gap-3">
                {{-- Thumb --}}
                @php $thumb = $submission->thumbnailImage(); $firstFile = $submission->images->first(); @endphp
                @if($thumb)
                    <img src="{{ $thumb->url }}" alt=""
                        class="w-12 h-12 rounded-lg object-cover flex-shrink-0">
                @elseif($firstFile && $firstFile->isVideo())
                    <div class="w-12 h-12 rounded-lg flex-shrink-0 relative overflow-hidden" style="background: #000;">
                        <video src="{{ $firstFile->url }}" class="w-full h-full object-cover" muted playsinline preload="metadata"></video>
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="w-5 h-5 rounded-full flex items-center justify-center" style="background: rgba(0,0,0,0.55);">
                                <svg class="w-3 h-3" style="color:#fff;" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="w-12 h-12 rounded-lg flex-shrink-0 flex items-center justify-center" style="background: var(--color-faint);">
                        <svg class="w-5 h-5 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                @endif

                <div class="flex-1 min-w-0">
                    <p class="font-display font-700 text-sm truncate" style="color: var(--color-text);">{{ $submission->character_name }}</p>
                    <p class="text-xs truncate" style="color: var(--color-muted);">{{ $submission->discord_user }} · {{ $submission->country }}</p>
                    <div class="flex gap-1 mt-1 flex-wrap">
                        <span class="badge" style="background: rgba(155,90,255,0.1); color: #c4a0ff; border-color: rgba(155,90,255,0.2); font-size: 0.6rem; padding: 0.1rem 0.35rem;">{{ $submission->gender }}</span>
                        <span class="badge" style="background: rgba(0,212,255,0.08); color: #80e0ff; border-color: rgba(0,212,255,0.15); font-size: 0.6rem; padding: 0.1rem 0.35rem;">{{ $submission->style }}</span>
                        <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--color-muted); border-color: var(--color-border); font-size: 0.6rem; padding: 0.1rem 0.35rem;">
                            📷 {{ $submission->images->count() }}
                        </span>
                    </div>
                </div>

                <div class="flex gap-1.5">
                    <a href="{{ route('admin.submissions.edit', $submission) }}" class="btn btn-secondary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    <form method="POST" action="{{ route('admin.submissions.destroy', $submission) }}"
                          onsubmit="return confirm('Delete {{ addslashes($submission->character_name) }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-center py-16" style="color: var(--color-muted);">
                <p class="font-display font-600">No submissions yet</p>
                <a href="{{ route('admin.submissions.create') }}" class="btn btn-primary btn-sm mt-3">Upload first submission</a>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $submissions->links() }}
    </div>
</div>
@endsection
