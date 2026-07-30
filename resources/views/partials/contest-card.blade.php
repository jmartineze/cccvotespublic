@php $badge = $contest->status_badge; @endphp
<div class="contest-card {{ $highlight ? 'glow-pink' : '' }}" style="{{ $highlight ? 'border-color: rgba(255,45,120,0.25)' : '' }}">
    {{-- Cover image --}}
    @if($contest->cover_image_url)
        <img src="{{ $contest->cover_image_url }}" alt="{{ $contest->name }}" class="cover">
    @else
        <div class="cover-placeholder">
            <svg class="w-10 h-10 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
    @endif

    <div class="p-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="font-display font-700 text-base truncate" style="color: var(--color-text);">{{ $contest->name }}</h2>
                @if($contest->description)
                    <p class="text-sm mt-0.5 line-clamp-2" style="color: var(--color-muted);">{{ $contest->description }}</p>
                @endif
            </div>
            <span class="badge badge-{{ $badge['class'] === 'badge-active' ? 'active' : ($badge['class'] === 'badge-closed' ? 'closed' : 'draft') }} flex-shrink-0">
                @if($contest->status === 'active')
                    <span class="w-1.5 h-1.5 rounded-full bg-current animate-pulse"></span>
                @endif
                {{ $badge['label'] }}
            </span>
        </div>

        <div class="flex items-center justify-between mt-4 pt-3" style="border-top: 1px solid var(--color-border);">
            <span class="text-xs" style="color: var(--color-muted);">
                <span class="font-mono font-700" style="color: var(--color-text);">{{ $contest->submissions_count }}</span> submissions
            </span>
            <div class="flex items-center gap-2">
                @if(!auth()->user()->isAnyAdmin() && $contest->status === 'active')
                    <a href="{{ route('judge.voting.index', $contest) }}" class="btn btn-primary btn-sm">
                        Vote
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                @elseif(!auth()->user()->isAnyAdmin() && $contest->status === 'closed')
                    <a href="{{ route('judge.voting.index', $contest) }}" class="btn btn-secondary btn-sm">My Votes</a>
                @endif
                @if($contest->status !== 'draft')
                    <a href="{{ route('results.show', $contest) }}" class="btn btn-secondary btn-sm">Results</a>
                @endif
            </div>
        </div>
    </div>
</div>
