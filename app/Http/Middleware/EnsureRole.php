<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureRole — Memastikan user memiliki role yang diperlukan.
 * Penggunaan: middleware('role:admin') atau middleware('role:user')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($user->role !== $role) {
            return response()->json(['message' => "Akses ditolak: diperlukan role '{$role}'"], 403);
        }

        return $next($request);
    }
}
