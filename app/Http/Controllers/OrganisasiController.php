<?php

namespace App\Http\Controllers;

use App\Models\Organisasi;
use App\Models\AnggotaOrganisasi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * OrganisasiController — CRUD organisasi dan manajemen anggota.
 */
class OrganisasiController extends Controller
{
    /** List semua organisasi milik user yang sedang login */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $organisasi = $user->organisasi()
            ->with(['creator', 'anggota.user'])
            ->withCount('anggota', 'transaksi')
            ->get()
            ->map(fn($org) => $this->formatOrganisasi($org));

        return response()->json(['data' => $organisasi]);
    }

    /** Buat organisasi baru */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:20|unique:organisasi,code|alpha_num',
            'description' => 'nullable|string',
            'type'        => 'nullable|string|max:100',
            'email'       => 'nullable|email',
            'phone'       => 'nullable|string|max:20',
            'address'     => 'nullable|string',
        ]);

        $organisasi = Organisasi::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'is_active'  => true,
        ]);

        // Otomatis jadikan pembuat sebagai ketua
        AnggotaOrganisasi::create([
            'user_id'       => $request->user()->id,
            'organisasi_id' => $organisasi->id,
            'role'          => 'ketua',
            'joined_at'     => now()->toDateString(),
            'is_active'     => true,
        ]);

        return response()->json([
            'message' => 'Organisasi berhasil dibuat',
            'data'    => $this->formatOrganisasi($organisasi->fresh(['creator', 'anggota'])),
        ], 201);
    }

    /** Detail organisasi */
    public function show(Request $request, Organisasi $organisasi): JsonResponse
    {
        $this->authorizeOrganisasiAccess($request->user(), $organisasi);
        $organisasi->load(['creator', 'anggota.user', 'transaksi' => fn($q) => $q->latest()->limit(5)]);

        return response()->json([
            'data' => [
                ...$this->formatOrganisasi($organisasi),
                'saldo'           => $organisasi->getSaldo(),
                'total_pemasukan' => $organisasi->getTotalPemasukan(),
                'total_pengeluaran' => $organisasi->getTotalPengeluaran(),
            ]
        ]);
    }

    /** Update organisasi */
    public function update(Request $request, Organisasi $organisasi): JsonResponse
    {
        $this->authorizeOrganisasiAccess($request->user(), $organisasi);
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'nullable|string|max:100',
            'email'       => 'nullable|email',
            'phone'       => 'nullable|string|max:20',
            'address'     => 'nullable|string',
            'dues_interval' => 'nullable|integer|min:1',
            'dues_amount'   => 'nullable|numeric|min:0',
            'status'        => 'nullable|string|in:Aktif,Non-aktif,Pending',
        ]);

        $organisasi->update($validated);

        // Hanya admin yang bisa mengubah status secara langsung
        if ($request->has('status') && $request->user()->isAdmin()) {
            $wasDeactivated = !$organisasi->is_active;
            
            if ($request->status === 'Aktif') {
                $organisasi->is_active = true;
                $organisasi->is_suspended = false;
                $organisasi->save();

                // Kirim email pengaktifan kembali jika sebelumnya non-aktif
                if ($wasDeactivated && $organisasi->email) {
                    try {
                        \Illuminate\Support\Facades\Mail::to($organisasi->email)
                            ->send(new \App\Mail\ReactivatedMail($organisasi->name));
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Gagal kirim email reactivated ke {$organisasi->email}: " . $e->getMessage());
                    }
                }
            } elseif ($request->status === 'Non-aktif') {
                $organisasi->is_active = false;
                $organisasi->save();

                // Kirim email penonaktifan jika sebelumnya aktif
                if ($wasDeactivated === false && $organisasi->email) {
                    try {
                        \Illuminate\Support\Facades\Mail::to($organisasi->email)
                            ->send(new \App\Mail\DeactivatedMail($organisasi->name));
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Gagal kirim email deactivated ke {$organisasi->email}: " . $e->getMessage());
                    }
                }
            }
        }

        return response()->json(['message' => 'Organisasi berhasil diupdate', 'data' => $this->formatOrganisasi($organisasi)]);
    }

    /** Upload logo organisasi */
    public function uploadLogo(Request $request, Organisasi $organisasi): JsonResponse
    {
        $this->authorizeOrganisasiAccess($request->user(), $organisasi);

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        // Hapus logo lama jika ada (dan bukan URL eksternal)
        if ($organisasi->logo && !str_starts_with($organisasi->logo, 'http')) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($organisasi->logo);
        }

        // Simpan file baru
        $path = $request->file('logo')->store('logos', 'public');
        $organisasi->update(['logo' => $path]);

        return response()->json([
            'message'  => 'Logo berhasil diperbarui',
            'logo_url' => $organisasi->fresh()->logo_url,
        ]);
    }

    /** Hapus logo organisasi (kembalikan ke default/inisial) */
    public function deleteLogo(Request $request, Organisasi $organisasi): JsonResponse
    {
        $this->authorizeOrganisasiAccess($request->user(), $organisasi);

        // Hapus file dari storage jika ada
        if ($organisasi->logo && !str_starts_with($organisasi->logo, 'http')) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($organisasi->logo);
        }

        $organisasi->update(['logo' => null]);

        return response()->json(['message' => 'Logo berhasil dihapus', 'logo_url' => null]);
    }

    /** Hapus organisasi (soft delete) */
    public function destroy(Request $request, Organisasi $organisasi): JsonResponse
    {
        $this->authorizeOrganisasiAccess($request->user(), $organisasi);
        $organisasi->delete();
        return response()->json(['message' => 'Organisasi berhasil dihapus']);
    }

    /** Tambah anggota ke organisasi */
    public function addAnggota(Request $request, Organisasi $organisasi): JsonResponse
    {
        $this->authorizeOrganisasiAccess($request->user(), $organisasi);
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'required|in:ketua,bendahara,sekretaris,anggota',
        ]);

        // Cek apakah sudah menjadi anggota
        $existing = AnggotaOrganisasi::where('user_id', $validated['user_id'])
            ->where('organisasi_id', $organisasi->id)->first();

        if ($existing) {
            return response()->json(['message' => 'User sudah menjadi anggota organisasi ini'], 422);
        }

        AnggotaOrganisasi::create([
            'user_id'       => $validated['user_id'],
            'organisasi_id' => $organisasi->id,
            'role'          => $validated['role'],
            'joined_at'     => now()->toDateString(),
            'is_active'     => true,
        ]);

        return response()->json(['message' => 'Anggota berhasil ditambahkan'], 201);
    }

    /** Hapus anggota dari organisasi */
    public function removeAnggota(Request $request, Organisasi $organisasi, User $user): JsonResponse
    {
        $this->authorizeOrganisasiAccess($request->user(), $organisasi);

        AnggotaOrganisasi::where('user_id', $user->id)
            ->where('organisasi_id', $organisasi->id)
            ->delete();

        return response()->json(['message' => 'Anggota berhasil dihapus']);
    }

    /** Update role anggota */
    public function updateRoleAnggota(Request $request, Organisasi $organisasi, User $user): JsonResponse
    {
        $this->authorizeOrganisasiAccess($request->user(), $organisasi);
        $validated = $request->validate(['role' => 'required|in:ketua,bendahara,sekretaris,anggota']);

        AnggotaOrganisasi::where('user_id', $user->id)
            ->where('organisasi_id', $organisasi->id)
            ->update(['role' => $validated['role']]);

        return response()->json(['message' => 'Role anggota berhasil diupdate']);
    }

    /** Helper: Cek apakah user anggota organisasi */
    private function authorizeOrganisasiAccess(User $user, Organisasi $organisasi): void
    {
        if ($user->isAdmin()) return;
        $isMember = AnggotaOrganisasi::where('user_id', $user->id)
            ->where('organisasi_id', $organisasi->id)->exists();
        if (!$isMember) abort(403, 'Anda bukan anggota organisasi ini');
    }

    /** Helper: Format data organisasi untuk response */
    private function formatOrganisasi(Organisasi $org): array
    {
        return [
            'id'          => $org->id,
            'name'        => $org->name,
            'code'        => $org->code,
            'description' => $org->description,
            'type'        => $org->type,
            'email'       => $org->email,
            'phone'       => $org->phone,
            'address'     => $org->address,
            'logo_url'    => $org->logo_url,
            'is_active'   => $org->is_active,
            'dues_interval' => $org->dues_interval,   // ✅ null jika belum pernah disimpan
            'dues_amount'   => $org->dues_amount,      // ✅ null jika belum pernah disimpan
            'created_at'  => $org->created_at?->toDateString(),
        ];
    }

    /** Aktifkan kembali organisasi yang dinonaktifkan otomatis */
    public function reactivate(Request $request, Organisasi $organisasi): JsonResponse
    {
        $this->authorizeOrganisasiAccess($request->user(), $organisasi);

        if ($organisasi->is_suspended) {
            return response()->json(['message' => 'Organisasi Anda tersuspend dan tidak dapat diaktifkan'], 403);
        }

        if (!$organisasi->is_active) {
            $organisasi->is_active = true;
            $organisasi->save();

            if ($organisasi->email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($organisasi->email)
                        ->send(new \App\Mail\ReactivatedMail($organisasi->name));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Gagal kirim email reactivated ke {$organisasi->email}: " . $e->getMessage());
                }
            }
        }

        return response()->json(['message' => 'Organisasi berhasil diaktifkan kembali']);
    }
}
