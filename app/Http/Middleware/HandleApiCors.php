<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HandleApiCors — Custom CORS Middleware
 *
 * Menangani CORS secara langsung menggunakan env variable,
 * tidak bergantung pada config/cors.php atau config cache.
 *
 * Urutan kerja:
 *  1. Cek apakah Origin request ada di daftar allowed origins
 *  2. Jika OPTIONS (preflight) → balas 204 dengan CORS headers langsung
 *  3. Jika request biasa → tambah CORS headers ke response PHP
 */
class HandleApiCors
{
    /**
     * Daftar origin yang diizinkan.
     * Dibaca dari env agar bisa diubah tanpa deploy ulang.
     */
    protected function allowedOrigins(): array
    {
        return array_filter([
            env('FRONTEND_URL', 'http://localhost:5173'),
            env('FRONTEND_URL_EXTRA'),
        ]);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin', '');

        // Cek apakah origin diizinkan
        $matchedOrigin = in_array($origin, $this->allowedOrigins(), true)
            ? $origin
            : null;

        // ── Handle OPTIONS preflight ──────────────────────────────────────
        // Balas langsung tanpa meneruskan ke routing/controller
        if ($request->isMethod('OPTIONS')) {
            $headers = [];

            if ($matchedOrigin) {
                $headers = [
                    'Access-Control-Allow-Origin'      => $matchedOrigin,
                    'Access-Control-Allow-Methods'     => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
                    'Access-Control-Allow-Headers'     => 'Accept, Authorization, Content-Type, Origin, X-Requested-With, X-XSRF-TOKEN',
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Max-Age'           => '7200',
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
