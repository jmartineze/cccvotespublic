<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#070711">
    <title>Reset Password — CCC Votes</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full flex items-center justify-center p-4" style="background: radial-gradient(ellipse at 20% 50%, rgba(155,90,255,0.12) 0%, transparent 60%), radial-gradient(ellipse at 80% 20%, rgba(255,45,120,0.10) 0%, transparent 50%), #070711;">

    <div class="w-full max-w-sm">

        {{-- Logo --}}
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center" style="background: linear-gradient(135deg, #ff2d78, #9b5aff); box-shadow: 0 0 40px rgba(155,90,255,0.4);">
                <svg class="w-9 h-9 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            </div>
            <h1 class="font-display text-2xl font-800 text-white">Forgot your password?</h1>
            <p class="text-sm mt-1" style="color: var(--color-muted);">Enter your email and we'll send you a reset link</p>
        </div>

        {{-- Form --}}
        <div class="card-glass p-6">
            @if(session('status'))
                <div class="alert alert-success mb-4">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-error mb-4">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="section-label block mb-1.5">Email</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="input {{ $errors->has('email') ? 'input-error' : '' }}"
                        placeholder="you@example.com"
                        autocomplete="email"
                        required
                        autofocus
                    >
                </div>
                <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 0.5rem;">
                    Send reset link
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </button>
            </form>
        </div>

        <p class="text-center text-sm mt-6">
            <a href="{{ route('login') }}" style="color: var(--color-muted);">&larr; Back to sign in</a>
        </p>
    </div>
</body>
</html>
