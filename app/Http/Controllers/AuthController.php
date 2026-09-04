<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Failed sign-in attempts allowed per identifier+IP before a cooldown.
     */
    private const MAX_ATTEMPTS = 5;

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $this->ensureIsNotRateLimited($request);

        // The identifier matches either the email or the username, so anyone
        // who has both can sign in with whichever they prefer.
        $identifier = $data['identifier'];
        $user = User::where('email', $identifier)->orWhere('username', $identifier)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($this->throttleKey($request));

            return back()->withErrors(['identifier' => 'Invalid credentials.'])->onlyInput('identifier');
        }

        RateLimiter::clear($this->throttleKey($request));
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Block further attempts once the identifier+IP pair trips the limit.
     */
    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'identifier' => "Too many login attempts. Please try again in {$seconds} seconds.",
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('identifier')).'|'.$request->ip());
    }
}
