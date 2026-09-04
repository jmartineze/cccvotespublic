@extends('layouts.app')

@section('title', 'Profile')

@section('content')
@php
    $u = auth()->user();
    $isJudge = $u->isJudge();
    $roleLabel = $u->isSuperAdmin() ? 'Super Admin'
        : ($u->isTenantAdmin() ? 'Tenant Admin'
        : ($u->isCoAdmin() ? 'Co-admin' : 'Judge'));
@endphp
<div class="px-4 py-5 max-w-2xl mx-auto">

    <div class="mb-6">
        <p class="section-label mb-1">Account</p>
        <h1 class="font-display text-xl font-800" style="color: var(--color-text);">Profile</h1>
    </div>

    {{-- Identity card --}}
    <div class="card p-4 mb-6 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full flex items-center justify-center font-display font-800 text-lg flex-shrink-0"
             style="background: linear-gradient(135deg, #9b5aff33, #ff2d7833); color: #c4a0ff; border: 1px solid rgba(155,90,255,0.25);">
            {{ strtoupper(substr($u->name, 0, 1)) }}
        </div>
        <div class="min-w-0">
            <p class="font-display font-700 text-base" style="color: var(--color-text);">{{ $u->name }}</p>
            <p class="text-sm truncate" style="color: var(--color-muted);">{{ $u->email ?? ($u->username ? '@'.$u->username : '—') }}</p>
            <span class="badge badge-active mt-1" style="font-size: 0.65rem;">{{ $roleLabel }}</span>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-error mb-4">
            <div>
                @foreach($errors->all() as $error)
                    <p class="text-xs">{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf

        {{-- Your details --}}
        <div class="card p-4 space-y-4">
            <h2 class="font-display font-700 text-base" style="color: var(--color-text);">Your details</h2>

            <div>
                <label class="section-label block mb-1.5">Name <span style="color: #ff2d78;">*</span></label>
                <input type="text" name="name" value="{{ old('name', $u->name) }}"
                    class="input {{ $errors->has('name') ? 'input-error' : '' }}" required>
                @error('name') <p class="error-msg">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="section-label block mb-1.5">
                    Username
                    @if($isJudge)
                        <span style="color: #ff2d78;">*</span>
                    @else
                        <span style="color: var(--color-muted); text-transform: none; font-weight: 400;">(optional)</span>
                    @endif
                </label>
                <input type="text" name="username" value="{{ old('username', $u->username) }}"
                    class="input {{ $errors->has('username') ? 'input-error' : '' }}"
                    placeholder="your_username" autocomplete="username" {{ $isJudge ? 'required' : '' }}>
                @error('username') <p class="error-msg">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="section-label block mb-1.5">
                    Email
                    @if($isJudge)
                        <span style="color: var(--color-muted); text-transform: none; font-weight: 400;">(optional)</span>
                    @else
                        <span style="color: #ff2d78;">*</span>
                    @endif
                </label>
                <input type="email" name="email" value="{{ old('email', $u->email) }}"
                    class="input {{ $errors->has('email') ? 'input-error' : '' }}"
                    placeholder="you@example.com" autocomplete="email" {{ $isJudge ? '' : 'required' }}>
                @error('email') <p class="error-msg">{{ $message }}</p> @enderror
                @if($isJudge)
                    <p class="text-xs mt-1" style="color: var(--color-muted);">Only needed if you want to use "forgot password".</p>
                @endif
            </div>
        </div>

        {{-- Change password --}}
        <div class="card p-4 space-y-4">
            <div>
                <h2 class="font-display font-700 text-base" style="color: var(--color-text);">Change password</h2>
                <p class="text-xs mt-0.5" style="color: var(--color-muted);">Leave blank to keep your current password.</p>
            </div>

            <div>
                <label class="section-label block mb-1.5">Current password</label>
                <input type="password" name="current_password"
                    class="input {{ $errors->has('current_password') ? 'input-error' : '' }}"
                    placeholder="Only when setting a new one" autocomplete="current-password">
                @error('current_password') <p class="error-msg">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="section-label block mb-1.5">New password</label>
                <input type="password" name="password"
                    class="input {{ $errors->has('password') ? 'input-error' : '' }}"
                    placeholder="Minimum 8 characters" minlength="8" autocomplete="new-password">
                @error('password') <p class="error-msg">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="section-label block mb-1.5">Confirm new password</label>
                <input type="password" name="password_confirmation" class="input"
                    placeholder="Repeat new password" autocomplete="new-password">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Save changes
        </button>
    </form>

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
