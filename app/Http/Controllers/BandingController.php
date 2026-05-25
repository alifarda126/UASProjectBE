<?php

namespace App\Http\Controllers;

use App\Models\BandingOrganisasi;
use App\Models\Organisasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * BandingController — Pengajuan banding oleh organisasi yang tersuspend.
 */
class BandingController extends Controller
{
    /** List banding organisasi milik user yang login */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Ambil semua organisasi yang dimiliki user
        $orgIds = $user->organisasi()->pluck('organisasi.id');

        $bandings = BandingOrganisasi::whereIn('organisasi_id', $orgIds)
            ->with('organisasi:id,name,type,is_suspended,suspended_reason')
            ->latest()
            ->get()
            ->map(fn($b) => [
                'id'           => $b->id,
                'organisasi'   => $b->organisasi ? [
                    'id'               => $b->organisasi->id,
                    'name'             => $b->organisasi->name,
                    'type'             => $b->organisasi->type,
                    'is_suspended'     => $b->organisasi->is_suspended,
                    'suspended_reason' => $b->organisasi->suspended_reason,
                ] : null,
                'message'      => $b->message,
                'evidence_url' => $b->evidence_url,
                'status'       => $b->status,
                'admin_note'   => $b->admin_note,
                'resolved_at'  => $b->resolved_at?->toISOString(),
                'created_at'   => $b->created_at?->toISOString(),
            ]);

        return response()->json(['data' => $bandings]);
    }

    /** Ajukan banding baru (dengan opsional upload bukti foto) */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'organisasi_id' => 'required|integer|exists:organisasi,id',
            'message'       => 'required|string|max:2000',
            'evidence'      => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120', // max 5MB
        ]);

        $user = $request->user();
        $orgId = $request->organisasi_id;

        // Pastikan user adalah anggota organisasi tersebut
        $isMember = $user->organisasi()->where('organisasi.id', $orgId)->exists();
        if (!$isMember) {
            return response()->json(['message' => 'Anda bukan anggota organisasi ini'], 403);
        }

        // Pastikan organisasi sedang tersuspend atau dinonaktifkan
        $organisasi = Organisasi::findOrFail($orgId);
        if (!$organisasi->is_suspended && $organisasi->is_active !== false) {
            return response()->json(['message' => 'Organisasi ini dalam keadaan aktif dan tidak memerlukan banding'], 422);
        }

        // Cek apakah sudah ada banding pending untuk organisasi ini
        $existingPending = BandingOrganisasi::where('organisasi_id', $orgId)
            ->where('status', 'pending')
            ->exists();
        if ($existingPending) {
            return response()->json(['message' => 'Sudah ada banding yang sedang menunggu proses untuk organisasi ini'], 422);
        }

        // Upload bukti (jika ada)
        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $evidencePath = $request->file('evidence')->store('banding-evidence', 'public');
        }

        $banding = BandingOrganisasi::create([
            'organisasi_id' => $orgId,
            'user_id'       => $user->id,
            'message'       => $request->message,
            'evidence_path' => $evidencePath,
            'status'        => 'pending',
        ]);

        return response()->json([
            'message' => 'Banding berhasil diajukan. Admin akan memeriksa pengajuan Anda.',
            'data'    => [
                'id'           => $banding->id,
                'status'       => $banding->status,
                'evidence_url' => $banding->evidence_url,
                'created_at'   => $banding->created_at->toISOString(),
            ],
        ], 201);
    }
}
