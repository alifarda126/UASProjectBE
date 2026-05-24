<?php

namespace App\Http\Controllers;

use App\Models\ProgramAnggaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ProgramAnggaranController — CRUD realisasi program kerja per organisasi.
 */
class ProgramAnggaranController extends Controller
{
    /** Ambil semua program untuk organisasi aktif user */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $orgId = $request->get('organisasi_id');

        $org = $orgId
            ? $user->organisasi()->find($orgId)
            : $user->organisasi()->first();

        if (!$org) {
            return response()->json(['data' => []]);
        }

        $programs = ProgramAnggaran::where('organisasi_id', $org->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn($p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'progress' => $p->progress,
            ]);

        return response()->json(['data' => $programs]);
    }

    /** Sync semua program (replace all) */
    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'organisasi_id' => 'required|integer|exists:organisasi,id',
            'programs'      => 'required|array',
            'programs.*.name'     => 'required|string|max:255',
            'programs.*.progress' => 'required|integer|min:0|max:100',
        ]);

        $user  = $request->user();
        $orgId = $request->organisasi_id;

        $isMember = $user->organisasi()->where('organisasi.id', $orgId)->exists();
        if (!$isMember) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        // Hapus semua program lama lalu buat ulang
        ProgramAnggaran::where('organisasi_id', $orgId)->delete();

        $programs = collect($request->programs)->map(fn($p) =>
            ProgramAnggaran::create([
                'organisasi_id' => $orgId,
                'name'          => $p['name'],
                'progress'      => min(100, max(0, (int) $p['progress'])),
            ])
        );

        return response()->json([
            'message' => 'Program berhasil disinkronkan',
            'data'    => $programs->map(fn($p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'progress' => $p->progress,
            ]),
        ]);
    }
}
