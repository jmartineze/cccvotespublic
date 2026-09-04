@extends('layouts.app')

@section('title', 'Admin · Judges')

@section('content')
<div class="px-4 py-5 max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <p class="section-label mb-1">Admin</p>
            <h1 class="font-display text-xl font-800" style="color: var(--color-text);">Judges &amp; Co-admins</h1>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Add Judge
        </a>
    </div>

    <div class="space-y-2">
        @forelse($judges as $judge)
            @php
                $isCo = $judge->tenant_role === 'co_admin';
                $shared = $judge->memberships_count > 1;
            @endphp
            <div class="card p-4" x-data="{ showReset: false, showRemove: false }">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 font-display font-800 text-sm"
                         style="background: linear-gradient(135deg, #9b5aff22, #ff2d7822); color: var(--color-muted); border: 1px solid var(--color-border);">
                        {{ strtoupper(substr($judge->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-display font-700 text-sm" style="color: var(--color-text);">{{ $judge->name }}</p>
                        <p class="text-xs truncate" style="color: var(--color-muted);">{{ '@'.$judge->username }}</p>
                    </div>
                    @if($isCo)
                        <span class="badge flex-shrink-0" style="background: rgba(0,212,255,0.12); color: #80e0ff; border-color: rgba(0,212,255,0.25); font-size: 0.65rem;">Co-admin</span>
                    @endif
                    @if($shared)
                        <span class="badge flex-shrink-0" style="background: rgba(255,180,0,0.12); color: #ffd280; border-color: rgba(255,180,0,0.25); font-size: 0.65rem;" title="Also participates in other tenants">Shared · {{ $judge->memberships_count }}</span>
                    @endif
                    <span class="badge badge-draft text-xs flex-shrink-0">
                        {{ $judge->votes_count }} {{ Str::plural('vote', $judge->votes_count) }}
                    </span>
                </div>

                @if($judge->id !== auth()->id())
                <div class="flex gap-2 mt-3 pt-3" style="border-top: 1px solid var(--color-border);">
                    @if($shared)
                        <span class="text-xs flex-1" style="color: var(--color-muted);">Shared account — the judge changes their own password.</span>
                    @else
                        <button @click="showReset = !showReset" class="btn btn-secondary btn-sm flex-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            Reset Password
                        </button>
                    @endif

                    @if(auth()->user()->isTenantAdmin())
                        @if($isCo)
                            <form method="POST" action="{{ route('admin.users.demote', $judge) }}"
                                  onsubmit="return confirm('Revoke co-admin from {{ addslashes($judge->name) }} for this tenant? They stay a judge and keep their votes.')">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-sm" title="Revoke co-admin">Revoke</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.users.promote', $judge) }}"
                                  onsubmit="return confirm('Make {{ addslashes($judge->name) }} a co-admin of this tenant? They can manage contests, submissions and judges here.')">
                                @csrf
                                <button type="submit" class="btn btn-cyan btn-sm" title="Promote to co-admin">Make co-admin</button>
                            </form>
                        @endif
                    @endif

                    <button @click="showRemove = true" class="btn btn-danger btn-sm" title="Remove from this tenant">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
                @endif

                {{-- Inline password reset form --}}
                @unless($shared)
                <div x-show="showReset" x-transition class="mt-3 pt-3" style="border-top: 1px solid var(--color-border);">
                    <form method="POST" action="{{ route('admin.users.reset-password', $judge) }}" class="space-y-3">
                        @csrf
                        <p class="section-label">New password for {{ $judge->name }}</p>
                        <input type="password" name="password" class="input" placeholder="New password (min 8 chars)" minlength="8" required>
                        <input type="password" name="password_confirmation" class="input" placeholder="Confirm new password" required>
                        <button type="submit" class="btn btn-cyan btn-sm btn-full">Set Password</button>
                    </form>
                </div>
                @endunless

                {{-- Remove confirmation --}}
                <div x-show="showRemove" x-transition class="mt-3 pt-3" style="border-top: 1px solid var(--color-border);">
                    <p class="text-sm mb-3" style="color: var(--color-muted);">
                        Removing <strong style="color: var(--color-text);">{{ $judge->name }}</strong> from this tenant
                        <strong style="color: var(--color-text);">won't delete their votes on closed contests</strong>.
                        Also delete their votes on active/draft contests?
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button @click="showRemove = false" class="btn btn-ghost btn-sm">Cancel</button>
                        <form method="POST" action="{{ route('admin.users.destroy', $judge) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-secondary btn-sm">Remove, keep open votes</button>
                        </form>
                        <form method="POST" action="{{ route('admin.users.destroy', $judge) }}">
                            @csrf @method('DELETE')
                            <input type="hidden" name="delete_open_votes" value="1">
                            <button type="submit" class="btn btn-danger btn-sm">Remove + delete open votes</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-16" style="color: var(--color-muted);">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="font-display font-600">No judges yet</p>
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm mt-3">Add first judge</a>
            </div>
        @endforelse
    </div>
</div>
@endsection
