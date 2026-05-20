<?php

namespace App\Http\Controllers;

use App\Models\KasAnggota;
use App\Models\Organisasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * KasAnggotaController — CRUD daftar anggota kas per organisasi.
 */
class KasAnggotaController extends Controller
{
    /** Ambil semua anggota kas untuk organisasi aktif user */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = $request->get('organisasi_id');

        // Cari organisasi milik user
        $org = $orgId
            ? $user->organisasi()->find($orgId)
            : $user->organisasi()->first();

        if (!$org) {
            return response()->json(['data' => []]);
        }

        $anggota = KasAnggota::where('organisasi_id', $org->id)
            ->where('is_active', true)
            ->orderBy('created_at')
            ->get()
            ->map(fn($a) => [
                'id'    => $a->id,
                'name'  => $a->name,
                'nim'   => $a->nim,
                'phone' => $a->phone,
            ]);

        return response()->json(['data' => $anggota]);
    }

    /** Tambah anggota kas baru */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organisasi_id' => 'required|integer|exists:organisasi,id',
            'name'          => 'required|string|max:255',
            'nim'           => 'nullable|string|max:50',
            'phone'         => 'nullable|string|max:20',
        ]);

        // Pastikan user adalah anggota organisasi tersebut
        $user = $request->user();
        $isMember = $user->organisasi()->where('organisasi.id', $validated['organisasi_id'])->exists();
        if (!$isMember) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $anggota = KasAnggota::create($validated);

        return response()->json([
            'message' => 'Anggota berhasil ditambahkan',
            'data'    => [
                'id'    => $anggota->id,
                'name'  => $anggota->name,
                'nim'   => $anggota->nim,
                'phone' => $anggota->phone,
            ],
        ], 201);
    }

    /** Hapus anggota kas (soft delete via is_active) */
    public function destroy(Request $request, KasAnggota $kasAnggota): JsonResponse
    {
        $user = $request->user();
        $isMember = $user->organisasi()
            ->where('organisasi.id', $kasAnggota->organisasi_id)
            ->exists();

        if (!$isMember && !$user->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $kasAnggota->update(['is_active' => false]);

        return response()->json(['message' => 'Anggota berhasil dihapus']);
    }
}
