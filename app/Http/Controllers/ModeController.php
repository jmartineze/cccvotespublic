<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ModeController extends Controller
{
    /**
     * Flip a tenant_admin / co_admin between the admin panel and the judge view.
     * The choice is persisted on the account.
     */
    public function toggle(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->canSwitchMode(), 403);

        $user->update(['judge_mode' => ! $user->judge_mode]);

        return redirect()->route('dashboard')->with(
            'success',
            $user->judge_mode
                ? 'Judge mode is on — you can browse and vote as a judge.'
                : 'Back to admin mode.'
        );
    }

    /**
     * Choose which tenant a multi-tenant co-admin is administering.
     */
    public function setTenant(Request $request): RedirectResponse
    {
        $user = $request->user();

        $tenantId = $request->integer('tenant_id');
        abort_unless(in_array($tenantId, $user->adminTenantIds(), true), 403);

        $request->session()->put('admin_tenant_id', $tenantId);

        return redirect()->back()->with('success', 'Switched tenant.');
    }
}
