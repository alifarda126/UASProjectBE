<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AnggotaOrganisasi;

/**
 * EnsureBendaharaOrKetua — Memastikan user memiliki role bendahara atau ketua.
 * Diperlukan untuk approve/reject transaksi.
 */
class EnsureBendaharaOrKetua
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Admin selalu diizinkan
        if ($user && $user->isAdmin()) {
            return $next($request);
        }

        $organisasiId = $request->get('organisasi_id');

        if (!$organisasiId) {
            // Coba ambil dari transaksi jika ada route parameter
            $transaksi = $request->route('transaksi');
            $organisasiId = $transaksi?->organisasi_id;
        }

        if (!$organisasiId) {
            return response()->json(['message' => 'organisasi_id diperlukan'], 422);
        }

        $anggota = AnggotaOrganisasi::where('user_id', $user->id)
            ->where('organisasi_id', $organisasiId)
            ->where('is_active', true)
            ->first();

        if (!$anggota || !in_array($anggota->role, ['ketua', 'bendahara'])) {
            return response()->json(['message' => 'Hanya ketua atau bendahara yang bisa melakukan aksi ini'], 403);
        }

        return $next($request);
    }
}
