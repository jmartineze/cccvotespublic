<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = User::where('role', 'tenant_admin')
            ->withCount(['contests' => fn ($q) => $q->withoutGlobalScopes()])
            ->orderBy('name')
            ->get();

        return view('superadmin.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        return view('superadmin.tenants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)->uncompromised(), 'confirmed'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'tenant_admin',
        ]);

        return redirect()->route('super-admin.tenants.index')
            ->with('success', "Tenant \"{$data['name']}\" created successfully.");
    }

    public function destroy(User $tenant): RedirectResponse
    {
        abort_if($tenant->role !== 'tenant_admin', 403);

        $tenant->delete();

        return redirect()->route('super-admin.tenants.index')
            ->with('success', 'Tenant removed.');
    }

    public function resetPassword(Request $request, User $tenant): RedirectResponse
    {
        abort_if($tenant->role !== 'tenant_admin', 403);

        $data = $request->validate([
            'password' => ['required', Password::min(8), 'confirmed'],
        ]);

        $tenant->update(['password' => Hash::make($data['password'])]);

        return redirect()->route('super-admin.tenants.index')
            ->with('success', "Password reset for {$tenant->name}.");
    }
}
