<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\Organisasi;
use App\Models\AnggotaOrganisasi;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * TransaksiController — CRUD transaksi keuangan + approve/reject.
 */
class TransaksiController extends Controller
{
    /** List transaksi dengan filter */
    public function index(Request $request): JsonResponse
    {
        try {
            $user         = $request->user();
            $organisasiId = $request->get('organisasi_id');
            $organisasi   = $this->getOrganisasi($user, $organisasiId);

            if (!$organisasi) {
                return response()->json(['data' => [], 'meta' => []]);
            }

            $query = $organisasi->transaksi()->with(['user:id,name,avatar', 'approver:id,name']);

            // Filter type
            if ($request->has('type') && in_array($request->type, ['pemasukan', 'pengeluaran'])) {
                $query->where('type', $request->type);
            }
            // Filter status
            if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
                $query->where('status', $request->status);
            }
            // Filter kategori
            if ($request->has('category')) {
                $query->where('category', 'like', '%' . $request->category . '%');
            }
            // Filter tanggal
            if ($request->has('date_from')) {
                $query->whereDate('date', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->whereDate('date', '<=', $request->date_to);
            }
            // Pencarian
            if ($request->has('search')) {
                $query->where('description', 'like', '%' . $request->search . '%');
            }

            $transaksi = $query->orderBy('date', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'data' => collect($transaksi->items())->map(fn($t) => $this->formatTransaksi($t)),
                'meta' => [
                    'total'        => $transaksi->total(),
                    'current_page' => $transaksi->currentPage(),
                    'last_page'    => $transaksi->lastPage(),
                    'per_page'     => $transaksi->perPage(),
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Debug 500: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /** Buat transaksi baru */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organisasi_id' => 'required|exists:organisasi,id',
            'type'          => 'required|in:pemasukan,pengeluaran',
            'category'      => 'required|string|max:100',
            'description'   => 'required|string|max:500',
            'amount'        => 'required|numeric|min:1',
            'date'          => 'required|date',
            'notes'         => 'nullable|string',
            'docs'          => 'nullable|array',
            'docs.*.name'   => 'required_with:docs|string|max:255',
            'docs.*.type'   => 'required_with:docs|string|max:100',
            'docs.*.dataUrl'=> 'required_with:docs|string',
        ]);

        $user       = $request->user();
        $organisasi = $this->getOrganisasi($user, $validated['organisasi_id']);

        if (!$organisasi) {
            return response()->json(['message' => 'Organisasi tidak ditemukan atau Anda bukan anggota'], 403);
        }

        try {
            $transaksi = Transaksi::create([
                ...$validated,
                'user_id'     => $user->id,
                'status'      => 'approved',  // ✅ Langsung approved
                'approved_by' => $user->id,
                'approved_at' => now(),
                'docs'        => $validated['docs'] ?? [],
            ]);

            return response()->json([
                'message' => 'Transaksi berhasil dibuat',
                'data'    => $this->formatTransaksi($transaksi->fresh(['user', 'approver'])),
            ], 201);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Transaksi store error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal menyimpan transaksi',
                'debug' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /** Detail transaksi */
    public function show(Request $request, Transaksi $transaksi): JsonResponse
    {
        $this->authorizeTransaksiAccess($request->user(), $transaksi);
        $transaksi->load(['user:id,name,avatar', 'approver:id,name', 'organisasi:id,name,code']);

        return response()->json(['data' => $this->formatTransaksi($transaksi)]);
    }

    /** Update transaksi (semua status) */
    public function update(Request $request, Transaksi $transaksi): JsonResponse
    {
        $this->authorizeTransaksiAccess($request->user(), $transaksi);

        $validated = $request->validate([
            'type'          => 'sometimes|in:pemasukan,pengeluaran',
            'category'      => 'sometimes|string|max:100',
            'description'   => 'sometimes|string|max:500',
            'amount'        => 'sometimes|numeric|min:1',
            'date'          => 'sometimes|date',
            'notes'         => 'nullable|string',
            'docs'          => 'nullable|array',
            'docs.*.name'   => 'required_with:docs|string|max:255',
            'docs.*.type'   => 'required_with:docs|string|max:100',
            'docs.*.dataUrl'=> 'required_with:docs|string',
        ]);

        // Jika docs dikirim (termasuk array kosong), update. Jika tidak dikirim, pertahankan yang lama.
        if (array_key_exists('docs', $validated)) {
            $transaksi->docs = $validated['docs'] ?? [];
        }
        $transaksi->update(array_diff_key($validated, ['docs' => null]));

        return response()->json(['message' => 'Transaksi berhasil diupdate', 'data' => $this->formatTransaksi($transaksi->fresh())]);
    }

    /** Hapus transaksi (semua status) */
    public function destroy(Request $request, Transaksi $transaksi): JsonResponse
    {
        $this->authorizeTransaksiAccess($request->user(), $transaksi);
        $transaksi->delete();
        return response()->json(['message' => 'Transaksi berhasil dihapus']);
    }

    /** Approve transaksi (hanya bendahara/ketua) */
    public function approve(Request $request, Transaksi $transaksi): JsonResponse
    {
        $user = $request->user();
        $this->authorizeBendaharaOrKetua($user, $transaksi->organisasi_id);

        if (!$transaksi->approve($user)) {
            return response()->json(['message' => 'Transaksi tidak bisa disetujui (sudah diproses)'], 422);
        }

        return response()->json(['message' => 'Transaksi berhasil disetujui', 'data' => $this->formatTransaksi($transaksi->fresh())]);
    }

    /** Reject transaksi (hanya bendahara/ketua) */
    public function reject(Request $request, Transaksi $transaksi): JsonResponse
    {
        $user      = $request->user();
        $validated = $request->validate(['notes' => 'nullable|string']);
        $this->authorizeBendaharaOrKetua($user, $transaksi->organisasi_id);

        if (!$transaksi->reject($user, $validated['notes'] ?? null)) {
            return response()->json(['message' => 'Transaksi tidak bisa ditolak (sudah diproses)'], 422);
        }

        return response()->json(['message' => 'Transaksi berhasil ditolak', 'data' => $this->formatTransaksi($transaksi->fresh())]);
    }

    /** Export transaksi ke CSV */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user         = $request->user();
        $organisasiId = $request->get('organisasi_id');
        $organisasi   = $this->getOrganisasi($user, $organisasiId);

        $transaksi = $organisasi
            ? $organisasi->transaksi()->with(['user:id,name'])->approved()->orderBy('date')->get()
            : collect();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="transaksi-' . now()->format('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($transaksi) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['No', 'Tanggal', 'Jenis', 'Kategori', 'Keterangan', 'Jumlah', 'Status', 'Dibuat Oleh']);
            foreach ($transaksi as $i => $t) {
                fputcsv($handle, [
                    $i + 1,
                    $t->date->format('d/m/Y'),
                    ucfirst($t->type),
                    $t->category,
                    $t->description,
                    $t->amount,
                    ucfirst($t->status),
                    $t->user?->name ?? '-',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    /* Helpers */
    private function formatTransaksi(Transaksi $t): array
    {
        return [
            'id'          => $t->id,
            'type'        => $t->type,
            'category'    => $t->category,
            'description' => $t->description,
            'amount'      => (float) $t->amount,
            'date'        => $t->date ? \Carbon\Carbon::parse($t->date)->toDateString() : null,
            'status'      => $t->status,
            'notes'       => $t->notes,
            'docs'        => is_string($t->docs) ? json_decode($t->docs, true) : ($t->docs ?? []),
            'created_at'  => $t->created_at ? \Carbon\Carbon::parse($t->created_at)->toISOString() : null,
            'approved_at' => $t->approved_at ? \Carbon\Carbon::parse($t->approved_at)->toISOString() : null,
            'user'        => $t->user ? ['id' => $t->user->id, 'name' => $t->user->name] : null,
            'approver'    => $t->approver ? ['id' => $t->approver->id, 'name' => $t->approver->name] : null,
        ];
    }

    private function getOrganisasi($user, ?int $id): ?Organisasi
    {
        if ($user->isAdmin() && $id) return Organisasi::find($id);
        if ($id) return $user->organisasi()->find($id);
        return $user->isAdmin() ? Organisasi::first() : $user->organisasi()->first();
    }

    private function authorizeTransaksiAccess($user, Transaksi $t): void
    {
        if ($user->isAdmin()) return;
        $isMember = AnggotaOrganisasi::where('user_id', $user->id)->where('organisasi_id', $t->organisasi_id)->exists();
        if (!$isMember) abort(403, 'Akses ditolak');
    }

    private function authorizeBendaharaOrKetua($user, int $organisasiId): void
    {
        if ($user->isAdmin()) return;
        $anggota = AnggotaOrganisasi::where('user_id', $user->id)->where('organisasi_id', $organisasiId)->first();
        if (!$anggota || !in_array($anggota->role, ['ketua', 'bendahara'])) {
            abort(403, 'Hanya ketua atau bendahara yang bisa menyetujui/menolak transaksi');
        }
    }
}
