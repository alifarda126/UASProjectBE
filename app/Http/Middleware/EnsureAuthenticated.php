<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\User;

/**
 * EnsureAuthenticated — Membaca token dari httpOnly cookie atau Authorization header.
 * Middleware ini diperlukan karena Sanctum normalnya baca dari header,
 * tapi kita menyimpan token di cookie httpOnly.
 */
class EnsureAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Coba baca token dari httpOnly cookie
        $token = $request->cookie('moneflo_token');

        // 2. Fallback ke Authorization Bearer header
        if (!$token) {
            $bearerToken = $request->bearerToken();
            $token = $bearerToken;
        }

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Validasi token via Sanctum PersonalAccessToken
        $personalToken = PersonalAccessToken::findToken($token);

        if (!$personalToken || !$personalToken->tokenable) {
            return response()->json(['message' => 'Token tidak valid atau sudah kadaluarsa'], 401);
        }

        $user = $personalToken->tokenable;

        if (!$user->is_active) {
            return response()->json(['message' => 'Akun Anda tidak aktif'], 403);
        }

        // Set access token agar bisa diakses via $user->currentAccessToken()
        $user->withAccessToken($personalToken);

        // Set user ke request agar tersedia di controller via $request->user()
        $request->setUserResolver(fn() => $user);
        auth()->setUser($user);

        return $next($request);
    }
}
