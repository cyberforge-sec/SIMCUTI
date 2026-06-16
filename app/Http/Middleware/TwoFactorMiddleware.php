<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // If user has 2FA enabled but hasn't verified yet, redirect to 2FA page
        if (Session::get('2fa_required') && !Session::get('2fa_verified')) {
            // Allow access to 2FA verification routes
            if ($request->routeIs('2fa.*') || $request->routeIs('logout')) {
                return $next($request);
            }
            return redirect()->route('2fa.show');
        }

        return $next($request);
    }
}
