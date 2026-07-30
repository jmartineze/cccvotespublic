@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="px-4 py-5 max-w-2xl mx-auto">

    <div class="mb-6">
        <p class="section-label mb-1">Account</p>
        <h1 class="font-display text-xl font-800" style="color: var(--color-text);">Profile</h1>
    </div>

    {{-- Identity card --}}
    <div class="card p-4 mb-6 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full flex items-center justify-center font-display font-800 text-lg flex-shrink-0"
             style="background: linear-gradient(135deg, #9b5aff33, #ff2d7833); color: #c4a0ff; border: 1px solid rgba(155,90,255,0.25);">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div class="min-w-0">
            <p class="font-display font-700 text-base" style="color: var(--color-text);">{{ auth()->user()->name }}</p>
            <p class="text-sm truncate" style="color: var(--color-muted);">{{ auth()->user()->email }}</p>
            <span class="badge badge-active mt-1" style="font-size: 0.65rem;">Judge</span>
        </div>
    </div>

    {{-- Change password --}}
    <div class="card p-4">
        <h2 class="font-display font-700 text-base mb-4" style="color: var(--color-text);">Change Password</h2>

        @if($errors->any())
            <div class="alert alert-error mb-4">
                <div>
                    @foreach($errors->all() as $error)
                        <p class="text-xs">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
            @csrf

            <div>
                <label class="section-label block mb-1.5">Current Password <span style="color: #ff2d78;">*</span></label>
                <input type="password" name="current_password"
                    class="input {{ $errors->has('current_password') ? 'input-error' : '' }}"
                    placeholder="Your current password" required>
                @error('current_password') <p class="error-msg">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="section-label block mb-1.5">New Password <span style="color: #ff2d78;">*</span></label>
                <input type="password" name="password"
                    class="input {{ $errors->has('password') ? 'input-error' : '' }}"
                    placeholder="Minimum 8 characters" minlength="8" required>
                @error('password') <p class="error-msg">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="section-label block mb-1.5">Confirm New Password <span style="color: #ff2d78;">*</span></label>
                <input type="password" name="password_confirmation"
                    class="input"
                    placeholder="Repeat new password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Update Password
            </button>
        </form>
    </div>

    {{-- Logout --}}
    <div class="mt-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-ghost btn-full" style="color: var(--color-muted);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Sign Out
            </button>
        </form>
    </div>
</div>
@endsection
