<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isJudge = $user->isJudge();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Judges/co-admins sign in by username; email-based accounts may still set one.
            'username' => [
                $isJudge ? 'required' : 'nullable',
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            // Email is required for the email-login roles, optional for judges.
            'email' => [
                $isJudge ? 'nullable' : 'required',
                'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            // Password only changes when a new one is typed.
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
        ], [
            'current_password.current_password' => 'The current password is incorrect.',
            'current_password.required_with' => 'Enter your current password to set a new one.',
        ]);

        $user->name = $data['name'];
        $user->username = ($data['username'] ?? null) ?: null;
        $user->email = ($data['email'] ?? null) ?: null;

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Profile updated.');
    }
}
