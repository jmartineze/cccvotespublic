@extends('layouts.app')

@section('title', 'Add Tenant')

@section('content')
<div class="px-4 py-5 max-w-2xl mx-auto">
    <div class="mb-5">
        <a href="{{ route('super-admin.tenants.index') }}" class="inline-flex items-center gap-1.5 text-sm mb-3" style="color: var(--color-muted);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Tenants
        </a>
        <h1 class="font-display text-xl font-800" style="color: var(--color-text);">Add Tenant</h1>
        <p class="text-sm mt-1" style="color: var(--color-muted);">A tenant admin can create their own contests and judges.</p>
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

    <form method="POST" action="{{ route('super-admin.tenants.store') }}" class="space-y-5">
        @csrf

        <div>
            <label class="section-label block mb-1.5">Display Name <span style="color: #ff2d78;">*</span></label>
            <input type="text" name="name" class="input {{ $errors->has('name') ? 'input-error' : '' }}"
                value="{{ old('name') }}" placeholder="Tenant Organization" required>
            @error('name') <p class="error-msg">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="section-label block mb-1.5">Email Address <span style="color: #ff2d78;">*</span></label>
            <input type="email" name="email" class="input {{ $errors->has('email') ? 'input-error' : '' }}"
                value="{{ old('email') }}" placeholder="tenant@example.com" required>
            @error('email') <p class="error-msg">{{ $message }}</p> @enderror
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
                Create Tenant
            </button>
            <a href="{{ route('super-admin.tenants.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
