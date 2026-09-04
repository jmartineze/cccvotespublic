@extends('layouts.app')

@section('title', 'Add Judge')

@section('content')
<div class="px-4 py-5 max-w-2xl mx-auto">
    <div class="mb-5">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-sm mb-3" style="color: var(--color-muted);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Judges
        </a>
        <h1 class="font-display text-xl font-800" style="color: var(--color-text);">Add Judge</h1>
        <p class="text-sm mt-1" style="color: var(--color-muted);">New judges can log in immediately and vote on active contests.</p>
    </div>

    @if($errors->any())
        <div class="alert alert-error mb-4">
            <div>
                <p class="font-600">Please fix the following:</p>
                <ul class="mt-1 text-xs space-y-0.5 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
        @csrf

        <div>
            <label class="section-label block mb-1.5">Display Name <span style="color: #ff2d78;">*</span></label>
            <input type="text" name="name" class="input {{ $errors->has('name') ? 'input-error' : '' }}"
                value="{{ old('name') }}" placeholder="Judge Nickname" required>
            @error('name') <p class="error-msg">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="section-label block mb-1.5">Username <span style="color: #ff2d78;">*</span></label>
            <input type="text" name="username" class="input {{ $errors->has('username') ? 'input-error' : '' }}"
                value="{{ old('username') }}" placeholder="judge_username" required>
            @error('username') <p class="error-msg">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="section-label block mb-1.5">Password <span style="color: #ff2d78;">*</span></label>
            <input type="password" name="password" class="input {{ $errors->has('password') ? 'input-error' : '' }}"
                placeholder="Minimum 8 characters" minlength="8" required>
            @error('password') <p class="error-msg">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="section-label block mb-1.5">Confirm Password <span style="color: #ff2d78;">*</span></label>
            <input type="password" name="password_confirmation" class="input"
                placeholder="Repeat password" required>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn btn-primary flex-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Create Judge
            </button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

@if(session('invite_prompt'))
    @php $prompt = session('invite_prompt'); @endphp
    <div x-data="{ open: true }" x-show="open" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.6);">
        <div class="card-glass p-6 w-full max-w-sm" @click.outside="open = false">
            <h2 class="font-display font-800 text-lg mb-2" style="color: var(--color-text);">This judge already exists</h2>
            <p class="text-sm mb-1" style="color: var(--color-muted);">
                <span class="font-mono" style="color: #00d4ff;">{{ '@'.$prompt['username'] }}</span> already belongs to
                <span style="color: var(--color-text);">{{ $prompt['name'] }}</span>.
            </p>
            @if($prompt['already_member'])
                <p class="text-sm mb-5" style="color: #ffb4b4;">They're already part of your tenant. Pick a different username to create a new account.</p>
                <div class="flex justify-end">
                    <button @click="open = false" class="btn btn-secondary btn-sm">OK</button>
                </div>
            @else
                <p class="text-sm mb-5" style="color: var(--color-muted);">Would you like to <strong style="color: var(--color-text);">invite them</strong> to your tenant instead? No new account is created and their password is not changed.</p>
                <div class="flex gap-2 justify-end">
                    <button @click="open = false" class="btn btn-ghost btn-sm">No, use another name</button>
                    <form method="POST" action="{{ route('admin.users.invite') }}">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $prompt['id'] }}">
                        <button type="submit" class="btn btn-primary btn-sm">Yes, invite</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endif
@endsection
