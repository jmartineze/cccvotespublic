@extends('layouts.app')

@section('title', 'Edit · ' . $contest->name)

@section('content')
<div class="px-4 py-5 max-w-2xl mx-auto">
    <div class="mb-5">
        <a href="{{ route('admin.contests.index') }}" class="inline-flex items-center gap-1.5 text-sm mb-3" style="color: var(--color-muted);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Contests
        </a>
        <h1 class="font-display text-xl font-800" style="color: var(--color-text);">Edit: {{ $contest->name }}</h1>
    </div>

    @include('partials.contest-form', ['contest' => $contest, 'action' => route('admin.contests.update', $contest), 'method' => 'PUT'])
</div>
@endsection
