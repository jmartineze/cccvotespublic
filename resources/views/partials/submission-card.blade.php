<a href="{{ route('judge.voting.show', [$contest, $submission]) }}" class="submission-card {{ $submission->has_voted ? 'voted' : '' }}">
    {{-- Image --}}
    @php $thumb = $submission->thumbnailImage(); $firstFile = $submission->images->first(); @endphp
    <div class="relative">
        @if($thumb)
            <img src="{{ $thumb->url }}" alt="{{ $submission->character_name }}" class="thumb" loading="lazy">
        @elseif($firstFile && $firstFile->isVideo())
            <div class="thumb-placeholder relative overflow-hidden" style="background: #000;">
                <video src="{{ $firstFile->url }}" class="w-full h-full object-cover" muted playsinline preload="metadata"></video>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background: rgba(0,0,0,0.55);">
                        <svg class="w-4 h-4" style="color:#fff;" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                    </div>
                </div>
            </div>
        @else
            <div class="thumb-placeholder">
                <svg class="w-8 h-8 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif

        {{-- Top badges row: HM left · Voted right --}}
        @if(!empty($submission->is_my_hm) || $submission->has_voted)
            <div class="absolute top-2 left-2 right-2 flex items-center justify-between gap-1 pointer-events-none">
                <span>
                    @if(!empty($submission->is_my_hm))
                        <span class="badge" style="background: rgba(0,0,0,0.6); color: #ffd700; border-color: rgba(255,180,0,0.5); font-size: 0.65rem;">⭐ HM</span>
                    @endif
                </span>
                <span>
                    @if($submission->has_voted)
                        <span class="badge badge-active text-xs">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Voted
                        </span>
                    @endif
                </span>
            </div>
        @endif

        {{-- Image count badge --}}
        @if($submission->images_count > 1)
            <div class="absolute bottom-2 left-2">
                <span class="badge" style="background: rgba(0,0,0,0.7); color: white; border-color: rgba(255,255,255,0.15);">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ $submission->images_count }}
                </span>
            </div>
        @endif
    </div>

    {{-- Info --}}
    <div class="p-2.5">
        <div class="flex items-baseline justify-between gap-1 min-w-0">
            <p class="font-display font-700 text-sm leading-tight truncate" style="color: var(--color-text);">{{ $submission->character_name }}</p>
            <p class="text-xs flex-shrink-0" style="color: var(--color-muted);">{{ $submission->country }}</p>
        </div>
        <div class="flex items-center gap-1 mt-1 flex-wrap">
            <span class="text-xs font-mono" style="color: #00d4ff; opacity: 0.8;">{{ '@'.$submission->discord_user }}</span>
            <span class="badge" style="background: rgba(155,90,255,0.12); color: #c4a0ff; border-color: rgba(155,90,255,0.2); font-size: 0.6rem; padding: 0.15rem 0.4rem;">{{ $submission->gender }}</span>
            <span class="badge" style="background: rgba(0,212,255,0.08); color: #80e0ff; border-color: rgba(0,212,255,0.2); font-size: 0.6rem; padding: 0.15rem 0.4rem;">{{ $submission->style }}</span>
        </div>
    </div>
</a>
