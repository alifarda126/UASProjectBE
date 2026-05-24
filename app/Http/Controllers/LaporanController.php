<?php

namespace App\Http\Controllers;

use App\Models\Organisasi;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * LaporanController — Laporan keuangan dan export.
 */
class LaporanController extends Controller
{
    /** Laporan keuangan periode tertentu */
    public function keuangan(Request $request): JsonResponse
    {
        $user         = $request->user();
        $organisasiId = $request->get('organisasi_id');
        $organisasi   = $this->getOrganisasi($user, $organisasiId);

        if (!$organisasi) {
            return response()->json(['message' => 'Organisasi tidak ditemukan'], 404);
        }

        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo   = $request->get('date_to',   Carbon::now()->endOfMonth()->toDateString());

        $query = $organisasi->transaksi()  // ✅ tanpa filter approved
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo);

        $transaksi = (clone $query)->with('user:id,name')->orderBy('date')->get();

        $totalPemasukan   = (clone $query)->pemasukan()->sum('amount');
        $totalPengeluaran = (clone $query)->pengeluaran()->sum('amount');

        // Breakdown per kategori
        $perKategori = (clone $query)->selectRaw('category, type, SUM(amount) as total, COUNT(*) as jumlah')
            ->groupBy('category', 'type')
            ->get();

        return response()->json([
            'data' => [
                'organisasi'        => ['id' => $organisasi->id, 'name' => $organisasi->name],
                'periode'           => ['dari' => $dateFrom, 'sampai' => $dateTo],
                'total_pemasukan'   => (float) $totalPemasukan,
                'total_pengeluaran' => (float) $totalPengeluaran,
                'saldo'             => (float) ($totalPemasukan - $totalPengeluaran),
                'transaksi'         => $transaksi->map(fn($t) => [
                    'id'          => $t->id,
                    'date'        => $t->date->toDateString(),
                    'type'        => $t->type,
                    'category'    => $t->category,
                    'description' => $t->description,
                    'amount'      => $t->amount,
                    'user'        => $t->user?->name,
                ]),
                'per_kategori' => $perKategori,
            ],
        ]);
    }

    /** Export laporan ke CSV */
    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user         = $request->user();
        $organisasiId = $request->get('organisasi_id');
        $organisasi   = $this->getOrganisasi($user, $organisasiId);
        $dateFrom     = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo       = $request->get('date_to',   Carbon::now()->endOfMonth()->toDateString());

        $transaksi = $organisasi
            ? $organisasi->transaksi()  // ✅ tanpa filter approved
                ->whereDate('date', '>=', $dateFrom)
                ->whereDate('date', '<=', $dateTo)
                ->with('user:id,name')->orderBy('date')->get()
            : collect();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"laporan-{$dateFrom}-{$dateTo}.csv\"",
        ];

        return response()->stream(function () use ($transaksi, $organisasi, $dateFrom, $dateTo) {
            $handle = fopen('php://output', 'w');
            // Header laporan
            fputcsv($handle, ["LAPORAN KEUANGAN - " . ($organisasi?->name ?? '')]);
            fputcsv($handle, ["Periode: {$dateFrom} s/d {$dateTo}"]);
            fputcsv($handle, []);
            fputcsv($handle, ['No', 'Tanggal', 'Jenis', 'Kategori', 'Keterangan', 'Jumlah', 'Dibuat Oleh']);

            foreach ($transaksi as $i => $t) {
                fputcsv($handle, [
                    $i + 1,
                    $t->date->format('d/m/Y'),
                    ucfirst($t->type),
                    $t->category,
                    $t->description,
                    'Rp ' . number_format($t->amount, 0, ',', '.'),
                    $t->user?->name ?? '-',
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['', '', '', '', 'Total Pemasukan',
                'Rp ' . number_format($transaksi->where('type', 'pemasukan')->sum('amount'), 0, ',', '.'), '']);
            fputcsv($handle, ['', '', '', '', 'Total Pengeluaran',
                'Rp ' . number_format($transaksi->where('type', 'pengeluaran')->sum('amount'), 0, ',', '.'), '']);

            fclose($handle);
        }, 200, $headers);
    }

    private function getOrganisasi($user, ?int $id): ?Organisasi
    {
        if ($user->isAdmin() && $id) return Organisasi::find($id);
        if ($id) return $user->organisasi()->find($id);
        return $user->isAdmin() ? Organisasi::first() : $user->organisasi()->first();
    }
}
