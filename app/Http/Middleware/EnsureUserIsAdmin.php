<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $administrator = $request->user('admin');

        if ($administrator?->role === 'admin' && $administrator->status === 'active') {
            return $next($request);
        }

        // End only the invalid administrator identity and preserve customer authentication.
        Auth::guard('admin')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->withErrors([
            'email' => 'Please sign in with an active administrator account.',
        ]);
    }
}
