<?php

/**
 * config/cors.php — Konfigurasi CORS untuk MoneFlo.
 *
 * supports_credentials: true WAJIB agar browser mengirim httpOnly cookie
 * allowed_origins: izinkan frontend dari env FRONTEND_URL
 *                  (localhost dev) & FRONTEND_URL_EXTRA (production Cloudflare)
 */

// Kumpulkan semua allowed origins dari env
$origins = array_filter([
    env('FRONTEND_URL', 'http://localhost:5173'),
    env('FRONTEND_URL_EXTRA'),      // opsional — URL tambahan (misal custom domain)
]);

return [
    'paths'                    => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => array_values($origins),
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => ['Content-Disposition'], // Untuk download file
    'max_age'                  => 0,
    'supports_credentials'     => true, // WAJIB untuk cookie/session lintas domain
];
