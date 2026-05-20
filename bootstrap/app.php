<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__ . '/../routes/web.php',
        api:      __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ── CORS: Izinkan credentials dari frontend ──
        $middleware->validateCsrfTokens(except: ['api/*']);

        // Daftarkan middleware alias kustom
        $middleware->alias([
            'auth.cookie'      => \App\Http\Middleware\EnsureAuthenticated::class,
            'role'             => \App\Http\Middleware\EnsureRole::class,
            'org.member'       => \App\Http\Middleware\EnsureOrganisasiMember::class,
            'org.bendahara'    => \App\Http\Middleware\EnsureBendaharaOrKetua::class,
        ]);

        // Kecualikan moneflo_token dari enkripsi agar bisa dibaca oleh Sanctum findToken
        $middleware->encryptCookies(except: [
            'moneflo_token',
        ]);

        // Izinkan credentials dikirim via API (untuk httpOnly cookie)
        $middleware->trustProxies(at: '*');

        // Tambahkan CORS header untuk semua respons API
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        // ── Middleware tracking session ──
        $middleware->append(\App\Http\Middleware\TrackUserSession::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
