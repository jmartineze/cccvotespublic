@extends('layouts.app')

@section('title', 'Super Admin · Tenants')

@section('content')
<div class="px-4 py-5 max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <p class="section-label mb-1">Super Admin</p>
            <h1 class="font-display text-xl font-800" style="color: var(--color-text);">Tenants</h1>
        </div>
        <a href="{{ route('super-admin.tenants.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Add Tenant
        </a>
    </div>

    <div class="space-y-2">
        @forelse($tenants as $tenant)
            <div class="card p-4" x-data="{ showReset: false }">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 font-display font-800 text-sm"
                         style="background: linear-gradient(135deg, #9b5aff22, #ff2d7822); color: var(--color-muted); border: 1px solid var(--color-border);">
                        {{ strtoupper(substr($tenant->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-display font-700 text-sm" style="color: var(--color-text);">{{ $tenant->name }}</p>
                        <p class="text-xs truncate" style="color: var(--color-muted);">{{ $tenant->email }}</p>
                    </div>
                    <span class="badge badge-draft text-xs flex-shrink-0">
                        {{ $tenant->contests_count }} {{ Str::plural('contest', $tenant->contests_count) }}
                    </span>
                </div>

                <div class="flex gap-2 mt-3 pt-3" style="border-top: 1px solid var(--color-border);">
                    <button @click="showReset = !showReset" class="btn btn-secondary btn-sm flex-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        Reset Password
                    </button>
                    <form method="POST" action="{{ route('super-admin.tenants.destroy', $tenant) }}"
                          onsubmit="return confirm('Remove tenant {{ addslashes($tenant->name) }}? Their contests and judges will also be deleted.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>

                <div x-show="showReset" x-transition class="mt-3 pt-3" style="border-top: 1px solid var(--color-border);">
                    <form method="POST" action="{{ route('super-admin.tenants.reset-password', $tenant) }}" class="space-y-3">
                        @csrf
                        <p class="section-label">New password for {{ $tenant->name }}</p>
                        <input type="password" name="password" class="input" placeholder="New password (min 8 chars)" minlength="8" required>
                        <input type="password" name="password_confirmation" class="input" placeholder="Confirm new password" required>
                        <button type="submit" class="btn btn-cyan btn-sm btn-full">Set Password</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-center py-16" style="color: var(--color-muted);">
                <p class="font-display font-600">No tenants yet</p>
                <a href="{{ route('super-admin.tenants.create') }}" class="btn btn-primary btn-sm mt-3">Add first tenant</a>
            </div>
        @endforelse
    </div>
</div>
@endsection
