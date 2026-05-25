<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HandleApiCors — Custom CORS Middleware
 *
 * Menangani CORS secara langsung menggunakan config() bukan env(),
 * karena env() mengembalikan null setelah `php artisan config:cache`
 * (Laravel skip load .env saat config sudah di-cache).
 *
 * Urutan kerja:
 *  1. Cek apakah Origin request ada di allowed_origins atau cocok dengan pattern
 *  2. Jika OPTIONS (preflight) → balas 204 dengan CORS headers langsung
 *  3. Jika request biasa → tambah CORS headers ke response PHP
 */
class HandleApiCors
{
    /**
     * Daftar origin yang diizinkan.
     * Dibaca dari config/cors.php (sudah di-cache oleh config:cache).
     */
    protected function allowedOrigins(): array
    {
        $origins = config('cors.allowed_origins', []);
        return is_array($origins) ? array_values(array_filter($origins)) : [];
    }

    /**
     * Daftar regex pattern untuk origin yang diizinkan.
     * Berguna untuk Cloudflare Pages preview URLs.
     */
    protected function allowedPatterns(): array
    {
        return config('cors.allowed_origins_patterns', []);
    }

    /**
     * Cek apakah origin diizinkan (exact match atau regex pattern).
     */
    protected function isOriginAllowed(string $origin): bool
    {
        if (!$origin) {
            return false;
        }

        // 1. Cek exact match
        if (in_array($origin, $this->allowedOrigins(), true)) {
            return true;
        }

        // 2. Cek regex patterns (misal: Cloudflare preview URLs)
        foreach ($this->allowedPatterns() as $pattern) {
            if (preg_match($pattern, $origin)) {
                return true;
            }
        }

        return false;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin', '');
        $matchedOrigin = $this->isOriginAllowed($origin) ? $origin : null;

        // ── Handle OPTIONS preflight ──────────────────────────────────────
        // Balas langsung tanpa meneruskan ke routing/controller
        if ($request->isMethod('OPTIONS')) {
            $headers = [];

            if ($matchedOrigin) {
                $allowedHeaders = config(
                    'cors.allowed_headers',
                    ['Accept', 'Authorization', 'Content-Type', 'Origin', 'X-Requested-With', 'X-XSRF-TOKEN']
                );

                $headers = [
                    'Access-Control-Allow-Origin'      => $matchedOrigin,
                    'Access-Control-Allow-Methods'     => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
                    'Access-Control-Allow-Headers'     => is_array($allowedHeaders)
                        ? implode(', ', $allowedHeaders)
                        : $allowedHeaders,
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Max-Age'           => (string) config('cors.max_age', 7200),
                    'Vary'                             => 'Origin',
                ];
            }

            return response('', 204, $headers);
        }

        // ── Handle request biasa (GET, POST, dll.) ────────────────────────
        $response = $next($request);

        if ($matchedOrigin) {
            $response->headers->set('Access-Control-Allow-Origin',      $matchedOrigin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Expose-Headers',    'Content-Disposition');
            $response->headers->set('Vary',                             'Origin');
        }

        return $response;
    }
}
