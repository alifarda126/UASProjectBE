<?php

namespace App\Http\Controllers;

use App\Models\BandingOrganisasi;
use App\Models\Notification;
use App\Models\Organisasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AdminOrganisasiController — Endpoint admin untuk manajemen organisasi.
 * Mencakup: list, force-delete, suspend, unsuspend, list banding, resolve banding.
 */
class AdminOrganisasiController extends Controller
{
    /** List semua organisasi (admin view) */
    public function index(): JsonResponse
    {
        $orgs = Organisasi::withCount(['anggota', 'transaksi', 'kasAnggota']) // ✅ kas_anggota_count
            ->with(['creator:id,name', 'bandings' => function($q) {
                $q->where('status', 'pending');
            }])
            ->withSum(['transaksi as total_pemasukan' => function ($q) {
                $q->where('type', 'pemasukan'); // ✅ tanpa filter status
            }], 'amount')
            ->withSum(['transaksi as total_pengeluaran' => function ($q) {
                $q->where('type', 'pengeluaran'); // ✅ tanpa filter status
            }], 'amount')
            ->withMax('users as last_active_at', 'last_login_at')
            ->withTrashed()
            ->whereNull('deleted_at')
            ->get();

        // Cek organisasi yang tidak aktif >= 30 hari untuk dinonaktifkan otomatis
        foreach ($orgs as $org) {
            if ($org->is_active) {
                // Tentukan tanggal acuan: last_active_at jika ada, atau created_at
                $lastActiveDate = $org->last_active_at ? \Carbon\Carbon::parse($org->last_active_at) : $org->created_at;
                
                if ($lastActiveDate && $lastActiveDate->copy()->addDays(30)->isPast()) {
                    $org->update([
                        'is_active' => false,
                    ]);
                    
                    // Kirim notifikasi email jika ada email
                    if ($org->email) {
                        try {
                            \Illuminate\Support\Facades\Mail::to($org->email)
                                ->send(new \App\Mail\DeactivatedMail($org->name));
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Gagal kirim email deactivation ke {$org->email}: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        $formattedOrgs = $orgs->map(fn($org) => $this->formatOrg($org));

        return response()->json(['data' => $formattedOrgs]);
    }

    /** Hapus permanen organisasi (forceDelete) */
    public function forceDestroy(int $id): JsonResponse
    {
        $organisasi = Organisasi::withTrashed()->findOrFail($id);
        $organisasi->forceDelete();
        return response()->json(['message' => 'Organisasi berhasil dihapus permanen']);
    }

    /** Tambah organisasi baru (admin) */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:20|unique:organisasi,code|alpha_num',
            'type'        => 'nullable|string|max:100',
            'email'       => 'nullable|email',
            'phone'       => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'status'      => 'nullable|in:Aktif,Non-aktif,Pending',
        ]);

        // Map status string ke boolean is_active
        $isActive = ($validated['status'] ?? 'Aktif') === 'Aktif';

        $organisasi = Organisasi::create([
            'name'        => $validated['name'],
            'code'        => $validated['code'],
            'type'        => $validated['type'] ?? null,
            'email'       => $validated['email'] ?? null,
            'phone'       => $validated['phone'] ?? null,
            'description' => $validated['description'] ?? null,
            'created_by'  => $request->user()->id,
            'is_active'   => $isActive,
        ]);

        return response()->json([
            'message' => 'Organisasi berhasil dibuat',
            'data'    => $this->formatOrg($organisasi->fresh()),
        ], 201);
    }


    /** Suspend organisasi + kirim notifikasi ke creator */
    public function suspend(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $organisasi = Organisasi::findOrFail($id);
        $organisasi->update([
            'is_suspended'     => true,
            'suspended_reason' => $request->reason,
            'suspended_at'     => now(),
        ]);

        // Kirim notifikasi ke creator organisasi
        if ($organisasi->creator) {
            Notification::create([
                'user_id' => $organisasi->creator->id,
                'title'   => 'Organisasi Anda Disuspend',
                'message' => "Organisasi \"{$organisasi->name}\" telah disuspend oleh admin. Alasan: {$request->reason}",
                'type'    => 'error',
                'icon'    => 'fa-ban',
            ]);
        }

        // Kirim email
        if ($organisasi->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($organisasi->email)
                    ->send(new \App\Mail\SuspendedMail($organisasi->name, $request->reason));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Gagal kirim email suspend ke {$organisasi->email}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Organisasi berhasil disuspend',
            'data'    => $this->formatOrg($organisasi->fresh()),
        ]);
    }

    /** Cabut suspend organisasi */
    public function unsuspend(int $id): JsonResponse
    {
        $organisasi = Organisasi::findOrFail($id);
        $organisasi->update([
            'is_suspended'     => false,
            'suspended_reason' => null,
            'suspended_at'     => null,
        ]);

        // Kirim notifikasi ke creator bahwa suspend dicabut
        if ($organisasi->creator) {
            Notification::create([
                'user_id' => $organisasi->creator->id,
                'title'   => 'Suspend Organisasi Dicabut',
                'message' => "Suspend pada organisasi \"{$organisasi->name}\" telah dicabut oleh admin. Organisasi Anda kini aktif kembali.",
                'type'    => 'success',
                'icon'    => 'fa-check-circle',
            ]);
        }

        // Kirim email pengaktifan kembali
        if ($organisasi->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($organisasi->email)
                    ->send(new \App\Mail\ReactivatedMail($organisasi->name));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Gagal kirim email reactivated ke {$organisasi->email}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Suspend berhasil dicabut',
            'data'    => $this->formatOrg($organisasi->fresh()),
        ]);
    }

    /** List semua pengajuan banding (admin) */
    public function bandings(Request $request): JsonResponse
    {
        $status = $request->query('status', 'all');

        $query = BandingOrganisasi::with(['organisasi:id,name,type,is_suspended', 'user:id,name,email'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $bandings = $query->get()->map(fn($b) => $this->formatBanding($b));

        return response()->json([
            'data'          => $bandings,
            'pending_count' => BandingOrganisasi::pending()->count(),
        ]);
    }

    /** Resolve banding: terima (accepted) atau tolak (rejected) */
    public function resolveBanding(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status'     => 'required|in:accepted,rejected',
            'admin_note' => 'nullable|string|max:1000',
        ]);
    
        $banding = BandingOrganisasi::with('organisasi.creator')->findOrFail($id);
    
        if ($banding->status !== 'pending') {
            return response()->json(['message' => 'Banding ini sudah diproses sebelumnya'], 422);
        }
    
        $banding->update([
            'status'      => $request->status,
            'admin_note'  => $request->admin_note,
            'resolved_at' => now(),
        ]);
    
        // Jika diterima, cabut suspend otomatis dan pastikan aktif
        if ($request->status === 'accepted' && $banding->organisasi) {
            $banding->organisasi->update([
                'is_active'        => true,
                'is_suspended'     => false,
                'suspended_reason' => null,
                'suspended_at'     => null,
            ]);

            // Kirim email pengaktifan kembali
            if ($banding->organisasi->email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($banding->organisasi->email)
                        ->send(new \App\Mail\ReactivatedMail($banding->organisasi->name));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Gagal kirim email reactivated ke {$banding->organisasi->email}: " . $e->getMessage());
                }
            }
        }
    
        // ✅ KIRIM NOTIFIKASI KE PENGAJU BANDING (user yang mengajukan)
        if ($banding->user) {
            $isAccepted = $request->status === 'accepted';
            $note = $request->admin_note ? "\n\nCatatan Admin: " . $request->admin_note : '';
            
            Notification::create([
                'user_id' => $banding->user_id,
                'title'   => $isAccepted ? '✅ Banding Diterima' : '❌ Banding Ditolak',
                'message' => $isAccepted
                    ? "Banding organisasi \"{$banding->organisasi?->name}\" telah DITERIMA. Suspend telah dicabut.{$note}"
                    : "Banding organisasi \"{$banding->organisasi?->name}\" telah DITOLAK.{$note}",
                'type'    => $isAccepted ? 'success' : 'warning',
                'icon'    => $isAccepted ? 'fa-check-circle' : 'fa-times-circle',
                'link'    => '/dashboard/pengaturan',
            ]);
        }

        // Kirim email penolakan banding ke email organisasi (jika ditolak)
        if ($request->status === 'rejected' && $banding->organisasi && $banding->organisasi->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($banding->organisasi->email)
                    ->send(new \App\Mail\BandingRejectedMail($banding->organisasi->name, $request->admin_note));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Gagal kirim email banding rejected ke {$banding->organisasi->email}: " . $e->getMessage());
            }
        }
    
        return response()->json([
            'message' => 'Banding berhasil diproses',
            'data'    => $this->formatBanding($banding->fresh(['organisasi', 'user'])),
        ]);
    }

    /**
     * ✅ TAMBAHKAN: Laporan keuangan untuk semua organisasi (admin)
     */
    public function laporanKeuangan(Request $request): JsonResponse
    {
        $organisasi = Organisasi::with(['transaksi', 'anggota'])->get();

        $laporan = $organisasi->map(function($org) {
            // ✅ Hitung semua transaksi tanpa filter status
            $totalPemasukan   = $org->transaksi->where('type', 'pemasukan')->sum('amount');
            $totalPengeluaran = $org->transaksi->where('type', 'pengeluaran')->sum('amount');

            return [
                'id'               => $org->id,
                'name'             => $org->name,
                'type'             => $org->type,
                'saldo'            => (float) ($totalPemasukan - $totalPengeluaran),
                'total_pemasukan'  => (float) $totalPemasukan,
                'total_pengeluaran'=> (float) $totalPengeluaran,
                'anggota_count'    => $org->anggota->count(),
                'transaksi_count'  => $org->transaksi->count(),
            ];
        });

        $totalSaldo      = $laporan->sum('saldo');
        $totalPemasukan  = $laporan->sum('total_pemasukan');
        $totalPengeluaran= $laporan->sum('total_pengeluaran');

        return response()->json([
            'data'    => $laporan,
            'summary' => [
                'total_saldo'        => (float) $totalSaldo,
                'total_pemasukan'    => (float) $totalPemasukan,
                'total_pengeluaran'  => (float) $totalPengeluaran,
                'total_organisasi'   => $organisasi->count(),
                'total_anggota'      => $laporan->sum('anggota_count'),
            ],
        ]);
    }

    /* ──────────────────────────────────────
     * PENGATURAN SISTEM
     * ────────────────────────────────────── */

    public function getSettings(): JsonResponse
    {
        $path = storage_path('app/settings.json');
        if (file_exists($path)) {
            $settings = json_decode(file_get_contents($path), true);
        } else {
            $settings = [
                'appName' => 'MoneFlo',
                'tagline' => 'Sistem Keuangan Organisasi',
                'sidebarSub' => 'Keuangan Organisasi',
                'contactEmail' => 'admin@moneflo.com',
                'logoUrl' => null,
                'logo2Url' => null,
                'faviconUrl' => null,
                'announcement' => '',
                'registOpen' => true,
            ];
        }
        return response()->json(['data' => $settings]);
    }

    public function saveSettings(Request $request): JsonResponse
    {
        $settings = $request->validate([
            'appName' => 'nullable|string',
            'tagline' => 'nullable|string',
            'sidebarSub' => 'nullable|string',
            'contactEmail' => 'nullable|email',
            'logoUrl' => 'nullable|string',
            'logo2Url' => 'nullable|string',
            'faviconUrl' => 'nullable|string',
            'announcement' => 'nullable|string',
            'registOpen' => 'nullable|boolean',
        ]);

        $path = storage_path('app/settings.json');
        
        $oldSettings = [];
        if (file_exists($path)) {
            $oldSettings = json_decode(file_get_contents($path), true) ?? [];
        }

        file_put_contents($path, json_encode($settings, JSON_PRETTY_PRINT));

        // Jika pengumuman berubah dan tidak kosong, kirim notifikasi ke semua user
        if (!empty($settings['announcement']) && $settings['announcement'] !== ($oldSettings['announcement'] ?? '')) {
            $users = \App\Models\User::all();
            $notifications = [];
            
            foreach ($users as $user) {
                $notifications[] = [
                    'user_id' => $user->id,
                    'title' => 'Pengumuman Sistem',
                    'message' => $settings['announcement'],
                    'type' => 'info',
                    'icon' => 'fa-bullhorn',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                try {
                    \Illuminate\Support\Facades\Mail::to($user->email)
                        ->send(new \App\Mail\AnnouncementMail($settings['announcement'], $settings['appName'] ?? 'MoneFlo'));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Gagal kirim email pengumuman ke {$user->email}: " . $e->getMessage());
                }
            }
            
            if (count($notifications) > 0) {
                \App\Models\Notification::insert($notifications);
            }
        }

        return response()->json(['message' => 'Settings saved successfully', 'data' => $settings]);
    }

    /* ──────────────────────────────────────
     * HELPERS
     * ────────────────────────────────────── */

    private function formatOrg(Organisasi $org): array
    {
        $pemasukan   = (float) ($org->total_pemasukan ?? 0);
        $pengeluaran = (float) ($org->total_pengeluaran ?? 0);

        return [
            'id'               => $org->id,
            'name'             => $org->name,
            'code'             => $org->code,
            'type'             => $org->type,
            'email'            => $org->email,
            'phone'            => $org->phone,
            'address'          => $org->address,
            'description'      => $org->description,
            'logo_url'         => $org->logo_url,
            'is_active'        => $org->is_active,
            'is_suspended'     => $org->is_suspended,
            'dues_interval'    => $org->dues_interval,
            'dues_amount'      => $org->dues_amount,
            'suspended_reason' => $org->suspended_reason,
            'suspended_at'     => $org->suspended_at?->toISOString(),
            'created_at'       => $org->created_at?->toDateString(),
            'anggota_count'    => $org->kas_anggota_count ?? 0,
            'transaksi_count'  => $org->transaksi_count ?? 0,
            'balance'          => $pemasukan - $pengeluaran,
            'total_pemasukan'  => $pemasukan,
            'total_pengeluaran'=> $pengeluaran,
            'last_active_at'   => $org->last_active_at, // Field baru
            'has_pending_banding' => $org->bandings->isNotEmpty(),
            'creator'          => $org->creator ? ['id' => $org->creator->id, 'name' => $org->creator->name] : null,
        ];
    }

    private function formatBanding(BandingOrganisasi $b): array
    {
        return [
            'id'           => $b->id,
            'organisasi'   => $b->organisasi ? ['id' => $b->organisasi->id, 'name' => $b->organisasi->name, 'type' => $b->organisasi->type] : null,
            'user'         => $b->user ? ['id' => $b->user->id, 'name' => $b->user->name, 'email' => $b->user->email] : null,
            'message'      => $b->message,
            'evidence_url' => $b->evidence_url,
            'status'       => $b->status,
            'admin_note'   => $b->admin_note,
            'resolved_at'  => $b->resolved_at?->toISOString(),
            'created_at'   => $b->created_at?->toISOString(),
        ];
    }
}