<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAuthProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        // Load only enabled providers that have an implemented login flow.
        $socialProviders = Schema::hasTable('social_auth_providers')
            ? SocialAuthProvider::query()
                ->whereIn('provider', ['google', 'telegram'])
                ->where('is_enabled', true)
                ->orderBy('sort_order')
                ->get()
                ->keyBy('provider')
            : collect();

        return view('auth.login', compact('socialProviders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = Str::lower($request->string('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many login attempts. Please try again shortly.']);
        }

        // Storefront authentication accepts customers only; administrators use the isolated admin login.
        if (! Auth::guard('web')->attempt([...$credentials, 'role' => 'customer', 'status' => 'active'], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'The email or password is incorrect.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user()->update(['last_login_at' => now()]);

        return redirect()->intended(route('profile'))->with('success', 'Welcome back to TrendShop.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // Invalidating the whole session would also sign out the independent admin guard.
        if (Auth::guard('admin')->check()) {
            $request->session()->regenerateToken();
        } else {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('products.index')->with('success', 'You have signed out.');
    }
}
