<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HonorableMention;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    /** Judge / co-admin management belongs to a tenant. Super-admins manage tenants. */
    private function tenantId(): int
    {
        $id = auth()->user()->currentTenantId();
        abort_if($id === null, 403, 'Manage judges from a tenant account.');

        return $id;
    }

    public function index(): View
    {
        $tenantId = $this->tenantId();

        $judges = User::query()
            ->whereHas('memberships', fn ($m) => $m->where('tenant_id', $tenantId))
            ->with(['memberships' => fn ($m) => $m->where('tenant_id', $tenantId)])
            ->withCount([
                // votes cast on *this* tenant's contests only (Contest carries TenantScope)
                'votes' => fn ($q) => $q->whereHas('submission.contest'),
                'memberships',
            ])
            ->orderBy('name')
            ->get()
            ->each(function ($u) use ($tenantId) {
                $u->tenant_role = optional($u->memberships->firstWhere('tenant_id', $tenantId))->role ?? 'judge';
            })
            ->sortByDesc(fn ($u) => $u->tenant_role === 'co_admin')
            ->values();

        return view('admin.users.index', compact('judges'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->tenantId();
        $username = (string) $request->input('username');

        $existing = User::where('username', $username)->first();

        // A judge with that username already exists → offer to add them
        // to this tenant instead of creating a duplicate.
        if ($existing && $existing->isJudge()) {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'alpha_dash'],
            ]);

            return back()->withInput()->with('invite_prompt', [
                'id' => $existing->id,
                'name' => $existing->name,
                'username' => $existing->username,
                'already_member' => $existing->belongsToTenant($tenantId),
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')],
            'password' => ['required', Password::min(8)->uncompromised(), 'confirmed'],
        ]);

        User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'role' => 'judge',
            'owner_id' => $tenantId,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Judge \"{$data['name']}\" created successfully.");
    }

    /**
     * Add an existing judge to the current tenant (no account/password change).
     */
    public function invite(Request $request): RedirectResponse
    {
        $tenantId = $this->tenantId();

        $target = User::findOrFail($request->integer('user_id'));
        abort_unless($target->isJudge(), 403);

        $membership = TenantMembership::firstOrCreate(
            ['tenant_id' => $tenantId, 'user_id' => $target->id],
            ['role' => 'judge'],
        );

        return redirect()->route('admin.users.index')->with(
            'success',
            $membership->wasRecentlyCreated
                ? "{$target->name} was added to your tenant."
                : "{$target->name} is already part of your tenant."
        );
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeTarget($user, ['judge', 'co_admin']);

        $tenantId = $this->tenantId();
        $membershipsLeft = $user->memberships()->where('tenant_id', '!=', $tenantId)->count();

        if ($membershipsLeft === 0) {
            // Nowhere left to sign in — remove the account and everything with it.
            $user->votes()->delete();
            HonorableMention::where('user_id', $user->id)->delete();
            $user->memberships()->delete();
            $user->delete();

            return redirect()->route('admin.users.index')->with('success', 'Judge removed.');
        }

        // Votes on *closed* contests are historical and always kept. Votes on
        // active/draft contests are dropped only when the admin opts in.
        if ($request->boolean('delete_open_votes')) {
            Vote::where('user_id', $user->id)
                ->whereHas('submission.contest', fn ($c) => $c->where('status', '!=', 'closed'))
                ->delete();
            HonorableMention::where('user_id', $user->id)
                ->whereHas('contest', fn ($c) => $c->where('status', '!=', 'closed'))
                ->delete();
        }

        $user->memberships()->where('tenant_id', $tenantId)->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "{$user->name} was removed from your tenant.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorizeTarget($user, ['judge', 'co_admin']);

        // A shared account (member of >1 tenant) can only be changed by the
        // judge themselves (self-service profile — coming soon).
        abort_if(
            $user->memberships()->count() > 1,
            403,
            'This judge participates in other tenants — they must change their own password.'
        );

        $data = $request->validate([
            'password' => ['required', Password::min(8), 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return redirect()->route('admin.users.index')
            ->with('success', "Password reset for {$user->name}.");
    }

    /**
     * Make a judge a co-admin of the current tenant. Tenant admins only.
     */
    public function promote(User $user): RedirectResponse
    {
        $tenantId = $this->tenantId();
        abort_unless(auth()->user()->isTenantAdmin(), 403, 'Only the tenant admin manages co-admins.');
        $this->authorizeTarget($user, ['judge']);

        $user->memberships()->where('tenant_id', $tenantId)->update(['role' => 'co_admin']);

        return redirect()->route('admin.users.index')
            ->with('success', "{$user->name} is now a co-admin of your tenant.");
    }

    /**
     * Revoke a co-admin back to judge for the current tenant. Tenant admins only.
     */
    public function demote(User $user): RedirectResponse
    {
        $tenantId = $this->tenantId();
        abort_unless(auth()->user()->isTenantAdmin(), 403, 'Only the tenant admin manages co-admins.');
        $this->authorizeTarget($user, ['co_admin']);

        $user->memberships()->where('tenant_id', $tenantId)->update(['role' => 'judge']);

        // No co-admin powers left anywhere → drop the stale judge-mode flag.
        if ($user->fresh()->memberships()->where('role', 'co_admin')->doesntExist()) {
            $user->update(['judge_mode' => false]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "{$user->name} is a judge again.");
    }

    /**
     * The target must have a membership in the current tenant with one of the
     * allowed pivot roles, and never be the acting user.
     */
    private function authorizeTarget(User $user, array $roles): void
    {
        $membership = $user->memberships()->where('tenant_id', $this->tenantId())->first();

        abort_if(
            $membership === null
            || $user->id === auth()->id()
            || ! in_array($membership->role, $roles, true),
            403
        );
    }
}
