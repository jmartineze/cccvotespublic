<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $judges = User::judgesOf(auth()->id())
            ->withCount('votes')
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('judges'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('users', 'username')->where('owner_id', auth()->id()),
            ],
            'password' => ['required', Password::min(8)->uncompromised(), 'confirmed'],
        ]);

        User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'role' => 'judge',
            'owner_id' => auth()->id(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Judge \"{$data['name']}\" created successfully.");
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->owner_id !== auth()->id() || $user->role !== 'judge', 403);

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Judge removed.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        abort_if($user->owner_id !== auth()->id() || $user->role !== 'judge', 403);

        $data = $request->validate([
            'password' => ['required', Password::min(8), 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return redirect()->route('admin.users.index')
            ->with('success', "Password reset for {$user->name}.");
    }
}
