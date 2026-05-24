<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AnggotaOrganisasi;

/**
 * EnsureOrganisasiMember — Memastikan user adalah anggota organisasi yang diminta.
 * Membaca organisasi_id dari request parameter atau route parameter.
 */
class EnsureOrganisasiMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Admin punya akses ke semua organisasi
        if ($user && $user->isAdmin()) {
            return $next($request);
        }

        $organisasiId = $request->route('organisasi') ?? $request->get('organisasi_id');

        if (!$organisasiId) {
            return $next($request); // Tidak ada organisasi_id berarti bukan route spesifik
        }

        $isMember = AnggotaOrganisasi::where('user_id', $user->id)
            ->where('organisasi_id', $organisasiId)
            ->where('is_active', true)
            ->exists();

        if (!$isMember) {
            return response()->json(['message' => 'Anda bukan anggota organisasi ini'], 403);
        }

        return $next($request);
    }
}
