<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        abort_if(auth()->user()->isAnyAdmin(), 403, 'Admins manage their password elsewhere.');

        return view('profile.edit');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        abort_if(auth()->user()->isAnyAdmin(), 403, 'Admins manage their password elsewhere.');

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::min(8), 'confirmed'],
        ], [
            'current_password.current_password' => 'The current password is incorrect.',
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}
