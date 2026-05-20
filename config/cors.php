<?php

/**
 * config/cors.php — Konfigurasi CORS untuk MoneFlo.
 *
 * supports_credentials: true  → WAJIB agar browser mengirim httpOnly cookie
 * allowed_origins             → daftar origin eksplisit dari env
 * allowed_origins_patterns    → regex fallback (Cloudflare preview URLs, dll.)
 * max_age                     → cache preflight 2 jam, kurangi request OPTIONS
 *
 * Env yang dibutuhkan di production (.env DomCloud):
 *   FRONTEND_URL=https://moneflo.pages.dev
 *   FRONTEND_URL_EXTRA=https://www.moneflo.pages.dev   ← opsional custom domain
 */

// Kumpulkan semua allowed origins eksplisit dari env
$origins = array_filter([
    env('FRONTEND_URL', 'http://localhost:5173'),
    env('FRONTEND_URL_EXTRA'),  // URL tambahan / custom domain (opsional)
]);

return [
    'paths'                    => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods'          => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins'          => array_values($origins),

    // Regex fallback: izinkan semua subdomain Cloudflare Pages (preview deploys)
    'allowed_origins_patterns' => [
        '#^https://[a-z0-9\-]+\.moneflo\.pages\.dev$#',
    ],

    'allowed_headers'          => ['Accept', 'Authorization', 'Content-Type', 'Origin', 'X-Requested-With', 'X-XSRF-TOKEN'],

    'exposed_headers'          => ['Content-Disposition'],

    // Cache hasil preflight selama 2 jam (7200 detik) — kurangi request OPTIONS
    'max_age'                  => 7200,

    'supports_credentials'     => true, // WAJIB untuk cookie/session lintas domain
];
