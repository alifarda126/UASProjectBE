<?php

namespace App\Http\Controllers;

use App\Models\Organisasi;
use App\Models\AnggotaOrganisasi;
use App\Models\Transaksi;
use App\Models\Agenda;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * DashboardController — Data statistik untuk halaman beranda/dashboard.
 */
class DashboardController extends Controller
{
    /**
     * Statistik utama dashboard:
     * total pemasukan, pengeluaran, saldo, jumlah pending.
     */
    public function stats(Request $request): JsonResponse
    {
        $user         = $request->user();
        $organisasiId = $request->get('organisasi_id');

        // Ambil organisasi yang diakses (pertama jika tidak ada parameter)
        $organisasi = $this->getOrganisasi($user, $organisasiId);

        if (!$organisasi) {
            return response()->json([
                'total_pemasukan'  => 0,
                'total_pengeluaran' => 0,
                'saldo'            => 0,
                'pending_count'    => 0,
                'approved_count'   => 0,
                'organisasi'       => null,
            ]);
        }

        $totalPemasukan   = (float) $organisasi->transaksi()->pemasukan()->approved()->sum('amount');
        $totalPengeluaran = (float) $organisasi->transaksi()->pengeluaran()->approved()->sum('amount');

        return response()->json([
            'total_pemasukan'   => $totalPemasukan,
            'total_pengeluaran' => $totalPengeluaran,
            'saldo'             => $totalPemasukan - $totalPengeluaran,
            'pending_count'     => $organisasi->transaksi()->pending()->count(),
            'approved_count'    => $organisasi->transaksi()->approved()->count(),
            'organisasi'        => [
                'id'   => $organisasi->id,
                'name' => $organisasi->name,
                'code' => $organisasi->code,
                'type' => $organisasi->type,
            ],
        ]);
    }

    /**
     * Data chart per bulan (6 bulan terakhir).
     */
    public function chartData(Request $request): JsonResponse
    {
        $user         = $request->user();
        $organisasiId = $request->get('organisasi_id');
        $organisasi   = $this->getOrganisasi($user, $organisasiId);

        if (!$organisasi) {
            return response()->json(['data' => []]);
        }

        $months = collect(range(5, 0))->map(function ($i) use ($organisasi) {
            $month = Carbon::now()->subMonths($i);
            $query = $organisasi->transaksi()->approved()
                ->whereYear('date', $month->year)
                ->whereMonth('date', $month->month);

            return [
                'month'      => $month->format('M Y'),
                'month_num'  => $month->format('Y-m'),
                'pemasukan'  => (float) (clone $query)->pemasukan()->sum('amount'),
                'pengeluaran' => (float) (clone $query)->pengeluaran()->sum('amount'),
            ];
        });

        return response()->json(['data' => $months]);
    }

    /**
     * 5 Transaksi terbaru (semua status).
     */
    public function recentTransactions(Request $request): JsonResponse
    {
        $user         = $request->user();
        $organisasiId = $request->get('organisasi_id');
        $organisasi   = $this->getOrganisasi($user, $organisasiId);

        if (!$organisasi) {
            return response()->json(['data' => []]);
        }

        $transaksi = $organisasi->transaksi()
            ->with(['user:id,name,avatar', 'approver:id,name'])
            ->latest('date')
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'id'          => $t->id,
                'type'        => $t->type,
                'category'    => $t->category,
                'description' => $t->description,
                'amount'      => $t->amount,
                'date'        => $t->date->toDateString(),
                'status'      => $t->status,
                'user'        => $t->user?->only(['id', 'name']),
            ]);

        return response()->json(['data' => $transaksi]);
    }

    /**
     * Agenda-agenda mendatang (maks 5).
     */
    public function upcomingAgendas(Request $request): JsonResponse
    {
        $user         = $request->user();
        $organisasiId = $request->get('organisasi_id');
        $organisasi   = $this->getOrganisasi($user, $organisasiId);

        if (!$organisasi) {
            return response()->json(['data' => []]);
        }

        $agendas = $organisasi->agendas()
            ->upcoming()
            ->orderBy('start_at')
            ->limit(5)
            ->get()
            ->map(fn($a) => [
                'id'          => $a->id,
                'title'       => $a->title,
                'description' => $a->description,
                'location'    => $a->location,
                'start_at'    => $a->start_at?->toISOString(),
                'end_at'      => $a->end_at?->toISOString(),
                'type'        => $a->type,
                'status'      => $a->status,
            ]);

        return response()->json(['data' => $agendas]);
    }

    /** Helper: Ambil organisasi user (pertama atau berdasarkan ID) */
    private function getOrganisasi($user, ?int $organisasiId): ?Organisasi
    {
        if ($user->isAdmin() && $organisasiId) {
            return Organisasi::find($organisasiId);
        }
        if ($organisasiId) {
            return $user->organisasi()->find($organisasiId);
        }
        return $user->isAdmin()
            ? Organisasi::first()
            : $user->organisasi()->first();
    }
}
