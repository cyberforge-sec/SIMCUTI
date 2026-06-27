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

        // Tambahkan header keamanan ke semua respons website
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        // Izinkan akses proxy Cloudflare Tunnel
        // Konfigurasi penerusan jaringan Cloudflare
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
 