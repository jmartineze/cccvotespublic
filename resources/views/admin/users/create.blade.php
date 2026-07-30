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
@endsection
