<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', $request->user()?->locale ?? config('app.locale'));
        App::setLocale(in_array($locale, ['km', 'en', 'zh'], true) ? $locale : 'km');

        return $next($request);
    }
}
