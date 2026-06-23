<?php

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SupabaseAuth;
use App\Http\Middleware\TwoFactorMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'supabase.auth' => SupabaseAuth::class,
            'role' => RoleMiddleware::class,
            '2fa' => TwoFactorMiddleware::class,
        ]);

        // Add security headers to all web responses
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        // Trust Cloudflare Tunnel proxy — reads X-Forwarded-Proto, X-Forwarded-For, etc.
        // Cloudflare Tunnel forwards traffic from edge to origin with these headers.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
