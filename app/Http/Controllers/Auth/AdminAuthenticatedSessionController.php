<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAuthenticatedSessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.admin-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = 'admin|'.Str::lower($request->string('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many administrator login attempts. Please try again shortly.']);
        }

        // Authenticate only active administrators through the isolated admin guard.
        if (! Auth::guard('admin')->attempt([...$credentials, 'role' => 'admin', 'status' => 'active'], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'The administrator email or password is incorrect.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        Auth::guard('admin')->user()?->update(['last_login_at' => now()]);

        return redirect()->intended(route('admin.dashboard'))->with('success', 'Welcome back to TrendShop Admin.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        // Preserve any customer identity stored in the separate storefront guard.
        Auth::guard('admin')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Administrator signed out.');
    }
}
