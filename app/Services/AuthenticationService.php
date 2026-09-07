<?php

namespace App\Services;

use App\Http\Requests\LoginRequest;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticationService
{
    public function login(LoginRequest $request): void
    {
        $key = Str::lower($request->string('email')->toString()).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.']);
        }
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi tidak sesuai.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        ActivityLog::create(['user_id' => Auth::id(), 'action' => 'auth.login', 'description' => 'Masuk ke aplikasi']);
    }

    public function logout(Request $request): void
    {
        ActivityLog::create(['user_id' => Auth::id(), 'action' => 'auth.logout', 'description' => 'Keluar dari aplikasi']);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
