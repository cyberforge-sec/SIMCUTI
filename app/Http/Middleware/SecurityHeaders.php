<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking: disallow framing from any origin
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Control referrer information sent with requests
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict browser features/APIs not used by the application
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy
        // Stricter CSP - removing unsafe-inline and unsafe-eval where possible
        $supabaseUrl = config('services.supabase.url', '');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; "
            . "script-src 'self' https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://static.cloudflareinsights.com; "
            . "style-src 'self' https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://fonts.googleapis.com; "
            . "img-src 'self' data: https: blob: https://github.githubassets.com https://www.gstatic.com; "
            . "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; "
            . "connect-src 'self' https://*.supabase.co wss://*.supabase.co https://static.cloudflareinsights.com; "
            . "frame-ancestors 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self'"
        );

        // XSS Protection (legacy, useful for older browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        return $response;
    }
}
