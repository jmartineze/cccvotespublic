@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="px-4 py-5 max-w-2xl mx-auto">

    {{-- Hero --}}
    <div class="mb-6">
        <p class="section-label mb-1">Welcome back</p>
        <h1 class="font-display text-2xl font-800" style="color: var(--color-text);">
            {{ auth()->user()->name }}
            @if(auth()->user()->isAnyAdmin())
                <span class="badge badge-closed text-xs align-middle ml-1">Admin</span>
            @endif
        </h1>
        <p class="text-sm mt-1" style="color: var(--color-muted);">Select a contest to start judging</p>
    </div>

    {{-- Active contests first --}}
    @php
        $active = $contests->where('status', 'active');
        $other  = $contests->where('status', '!=', 'active');
    @endphp

    @if($active->isNotEmpty())
        <div class="mb-2 flex items-center gap-2">
            <span class="section-label">Active now</span>
            <span class="badge badge-active">{{ $active->count() }}</span>
        </div>
        <div class="space-y-3 mb-6">
            @foreach($active as $contest)
                @include('partials.contest-card', ['contest' => $contest, 'highlight' => true])
            @endforeach
        </div>
    @endif

    @if($other->isNotEmpty())
        <p class="section-label mb-2">Other contests</p>
        <div class="space-y-3">
            @foreach($other as $contest)
                @include('partials.contest-card', ['contest' => $contest, 'highlight' => false])
            @endforeach
        </div>
    @endif

    @if($contests->isEmpty())
        <div class="text-center py-16" style="color: var(--color-muted);">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <p class="font-display font-600">No contests yet</p>
            @if(auth()->user()->isAnyAdmin())
                <a href="{{ route('admin.contests.create') }}" class="btn btn-primary btn-sm mt-3">Create first contest</a>
            @endif
        </div>
    @endif
</div>
@endsection
