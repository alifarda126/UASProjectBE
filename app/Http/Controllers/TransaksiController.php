<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\Organisasi;
use App\Models\AnggotaOrganisasi;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TransaksiController extends Controller
{
    /**
     * List transaksi dengan filter
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user         = $request->user();
            $organisasiId = $request->get('organisasi_id');
            $organisasi   = $this->getOrganisasi($user, $organisasiId);

            if (!$organisasi) {
                return response()->json([
                    'data' => [],
                    'meta' => []
                ]);
            }

            $query = $organisasi->transaksi()
                ->with([
                    'user:id,name,avatar',
                    'approver:id,name'
                ]);

            // Filter type
            if (
                $request->has('type') &&
                in_array($request->type, ['pemasukan', 'pengeluaran'])
            ) {
                $query->where('type', $request->type);
            }

            // Filter status
            if (
                $request->has('status') &&
                in_array($request->status, ['pending', 'approved', 'rejected'])
            ) {
                $query->where('status', $request->status);
            }

            // Filter kategori
            if ($request->has('category')) {
                $query->where(
                    'category',
                    'like',
                    '%' . $request->category . '%'
                );
            }

            // Filter tanggal
            if ($request->has('date_from')) {
                $query->whereDate(
                    'date',
                    '>=',
                    $request->date_from
                );
            }

            if ($request->has('date_to')) {
                $query->whereDate(
                    'date',
                    '<=',
                    $request->date_to
                );
            }

            // Pencarian
            if ($request->has('search')) {
                $query->where(
                    'description',
                    'like',
                    '%' . $request->search . '%'
                );
            }

            /*
             * 1. Ambil IDs terlebih dahulu untuk menghindari
             * MySQL Out of Sort Memory karena kolom docs
             * dapat berisi LONGTEXT.
             */
            $paginator = $query
                ->select('transaksi.id')
                ->orderBy('date', 'desc')
                ->paginate(
                    $request->get('per_page', 15)
                );

            $ids = collect($paginator->items())
                ->pluck('id')
                ->toArray();

            /*
             * 2. Ambil data lengkap berdasarkan IDs.
             */
            $fullModels = Transaksi::with([
                'user:id,name,avatar',
                'approver:id,name'
            ])
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            /*
             * 3. Gabungkan kembali sesuai urutan paginator.
             */
            $transaksiItems = collect($paginator->items())
                ->map(function ($t) use ($fullModels) {
                    return $fullModels[$t->id] ?? null;
                })
                ->filter()
                ->values();

            return response()->json([
                'data' => $transaksiItems->map(
                    fn ($t) => $this->formatTransaksi($t)
                ),

                'meta' => [
                    'total'        => $paginator->total(),
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                ]
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'message' => 'Debug 500: ' . $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine()
            ], 500);
        }
    }


    /**
     * Buat transaksi baru
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organisasi_id'    => 'required|exists:organisasi,id',
            'type'             => 'required|in:pemasukan,pengeluaran',
            'category'         => 'required|string|max:100',
            'description'      => 'required|string|max:500',
            'amount'           => 'required|numeric|min:1',
            'date'             => 'required|date',
            'notes'            => 'nullable|string',

            /*
             * docs adalah metadata file hasil upload S3.
             */
            'docs'             => 'nullable|array|max:5',
            'docs.*.url'       => 'required_with:docs|string|url',
            'docs.*.path'      => 'nullable|string|max:1000',
            'docs.*.name'      => 'required_with:docs|string|max:255',
            'docs.*.mime_type' => 'required_with:docs|string|max:100',
            'docs.*.size'      => 'required_with:docs|integer|min:0',
        ]);

        $user       = $request->user();
        $organisasi = $this->getOrganisasi(
            $user,
            $validated['organisasi_id']
        );

        if (!$organisasi) {
            return response()->json([
                'message' =>
                    'Organisasi tidak ditemukan atau Anda bukan anggota'
            ], 403);
        }

        try {

            $transaksi = Transaksi::create([
                ...$validated,

                'user_id'     => $user->id,
                'status'      => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'docs'        => $validated['docs'] ?? [],
            ]);

            return response()->json([
                'message' => 'Transaksi berhasil dibuat',

                'data' => $this->formatTransaksi(
                    $transaksi->fresh([
                        'user',
                        'approver'
                    ])
                ),
            ], 201);

        } catch (\Throwable $e) {

            Log::error(
                'Transaksi store error: ' . $e->getMessage()
            );

            return response()->json([
                'message' => 'Gagal menyimpan transaksi',
                'debug'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine()
            ], 500);
        }
    }


    /**
     * Detail transaksi
     */
    public function show(
        Request $request,
        Transaksi $transaksi
    ): JsonResponse {

        $this->authorizeTransaksiAccess(
            $request->user(),
            $transaksi
        );

        $transaksi->load([
            'user:id,name,avatar',
            'approver:id,name',
            'organisasi:id,name,code'
        ]);

        return response()->json([
            'data' => $this->formatTransaksi($transaksi)
        ]);
    }


    /**
     * Update transaksi
     */
    public function update(
        Request $request,
        Transaksi $transaksi
    ): JsonResponse {

        $this->authorizeTransaksiAccess(
            $request->user(),
            $transaksi
        );

        $validated = $request->validate([
            'type'             => 'sometimes|in:pemasukan,pengeluaran',
            'category'         => 'sometimes|string|max:100',
            'description'      => 'sometimes|string|max:500',
            'amount'           => 'sometimes|numeric|min:1',
            'date'             => 'sometimes|date',
            'notes'            => 'nullable|string',

            'docs'             => 'nullable|array|max:5',
            'docs.*.url'       => 'required_with:docs|string|url',
            'docs.*.path'      => 'nullable|string|max:1000',
            'docs.*.name'      => 'required_with:docs|string|max:255',
            'docs.*.mime_type' => 'required_with:docs|string|max:100',
            'docs.*.size'      => 'required_with:docs|integer|min:0',
        ]);

        /*
         * Jika docs dikirim, termasuk array kosong,
         * update docs.
         *
         * Jika docs tidak dikirim, docs lama dipertahankan.
         */
        if (array_key_exists('docs', $validated)) {
            $transaksi->docs = $validated['docs'] ?? [];
        }

        $transaksi->update(
            array_diff_key(
                $validated,
                ['docs' => null]
            )
        );

        return response()->json([
            'message' => 'Transaksi berhasil diupdate',

            'data' => $this->formatTransaksi(
                $transaksi->fresh()
            ),
        ]);
    }


    /**
     * Hapus transaksi
     */
    public function destroy(
        Request $request,
        Transaksi $transaksi
    ): JsonResponse {

        $this->authorizeTransaksiAccess(
            $request->user(),
            $transaksi
        );

        $transaksi->delete();

        return response()->json([
            'message' => 'Transaksi berhasil dihapus'
        ]);
    }


    /**
     * Approve transaksi
     */
    public function approve(
        Request $request,
        Transaksi $transaksi
    ): JsonResponse {

        $user = $request->user();

        $this->authorizeBendaharaOrKetua(
            $user,
            $transaksi->organisasi_id
        );

        if (!$transaksi->approve($user)) {
            return response()->json([
                'message' =>
                    'Transaksi tidak bisa disetujui (sudah diproses)'
            ], 422);
        }

        return response()->json([
            'message' =>
                'Transaksi berhasil disetujui',

            'data' => $this->formatTransaksi(
                $transaksi->fresh()
            ),
        ]);
    }


    /**
     * Reject transaksi
     */
    public function reject(
        Request $request,
        Transaksi $transaksi
    ): JsonResponse {

        $user      = $request->user();
        $validated = $request->validate([
            'notes' => 'nullable|string'
        ]);

        $this->authorizeBendaharaOrKetua(
            $user,
            $transaksi->organisasi_id
        );

        if (!$transaksi->reject(
            $user,
            $validated['notes'] ?? null
        )) {

            return response()->json([
                'message' =>
                    'Transaksi tidak bisa ditolak (sudah diproses)'
            ], 422);
        }

        return response()->json([
            'message' =>
                'Transaksi berhasil ditolak',

            'data' => $this->formatTransaksi(
                $transaksi->fresh()
            ),
        ]);
    }


    /**
     * Export transaksi ke CSV
     */
    public function export(
        Request $request
    ): \Symfony\Component\HttpFoundation\StreamedResponse {

        $user         = $request->user();
        $organisasiId = $request->get('organisasi_id');
        $organisasi   = $this->getOrganisasi(
            $user,
            $organisasiId
        );

        $transaksi = $organisasi
            ? $organisasi
                ->transaksi()
                ->with(['user:id,name'])
                ->approved()
                ->orderBy('date')
                ->get()
            : collect();

        $headers = [
            'Content-Type' =>
                'text/csv',

            'Content-Disposition' =>
                'attachment; filename="transaksi-'
                . now()->format('Y-m-d')
                . '.csv"',
        ];

        return response()->stream(
            function () use ($transaksi) {

                $handle = fopen(
                    'php://output',
                    'w'
                );

                fputcsv(
                    $handle,
                    [
                        'No',
                        'Tanggal',
                        'Jenis',
                        'Kategori',
                        'Keterangan',
                        'Jumlah',
                        'Status',
                        'Dibuat Oleh'
                    ]
                );

                foreach ($transaksi as $i => $t) {

                    fputcsv(
                        $handle,
                        [
                            $i + 1,
                            $t->date->format('d/m/Y'),
                            ucfirst($t->type),
                            $t->category,
                            $t->description,
                            $t->amount,
                            ucfirst($t->status),
                            $t->user?->name ?? '-',
                        ]
                    );
                }

                fclose($handle);
            },
            200,
            $headers
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */


    /**
     * Format transaksi untuk response API.
     *
     * Yang penting di sini:
     *
     * docs lama tetap menggunakan path yang sama,
     * tetapi URL-nya dibuat ulang menjadi signed URL.
     */
    private function formatTransaksi(
        Transaksi $t
    ): array {

        $docs = is_string($t->docs)
            ? json_decode($t->docs, true)
            : ($t->docs ?? []);

        if (!is_array($docs)) {
            $docs = [];
        }

        /*
         * Generate URL baru untuk setiap file.
         */
        $docs = collect($docs)
            ->map(function ($doc) {

                if (!is_array($doc)) {
                    return $doc;
                }

                $path = $this->resolveDocPath($doc);

                /*
                 * Kalau path ditemukan, buat signed URL baru.
                 */
                if ($path) {

                    try {

                        $doc['path'] = $path;

                        $doc['url'] = Storage::disk('s3')
                            ->temporaryUrl(
                                $path,
                                now()->addMinutes(60)
                            );

                    } catch (\Throwable $e) {

                        Log::warning(
                            'Gagal membuat signed URL dokumen transaksi',
                            [
                                'path'    => $path,
                                'message' => $e->getMessage(),
                            ]
                        );
                    }
                }

                return $doc;

            })
            ->values()
            ->all();

        return [
            'id'          => $t->id,
            'type'        => $t->type,
            'category'    => $t->category,
            'description' => $t->description,
            'amount'      => (float) $t->amount,

            'date' => $t->date
                ? \Carbon\Carbon::parse(
                    $t->date
                )->toDateString()
                : null,

            'status' => $t->status,
            'notes'  => $t->notes,

            'docs' => $docs,

            'created_at' => $t->created_at
                ? \Carbon\Carbon::parse(
                    $t->created_at
                )->toISOString()
                : null,

            'approved_at' => $t->approved_at
                ? \Carbon\Carbon::parse(
                    $t->approved_at
                )->toISOString()
                : null,

            'user' => $t->user
                ? [
                    'id'   => $t->user->id,
                    'name' => $t->user->name
                ]
                : null,

            'approver' => $t->approver
                ? [
                    'id'   => $t->approver->id,
                    'name' => $t->approver->name
                ]
                : null,
        ];
    }


    /**
     * Cari path file dari metadata docs.
     *
     * Prioritas:
     *
     * 1. doc.path
     * 2. URL S3 yang tersimpan pada doc.url
     *
     * Ini penting supaya file transaksi lama yang sebelumnya
     * hanya menyimpan URL tetap bisa dicoba dipulihkan.
     */
    private function resolveDocPath(
        array $doc
    ): ?string {

        /*
         * Prioritas pertama: path yang memang disimpan
         * oleh UploadController.
         */
        if (
            isset($doc['path']) &&
            is_string($doc['path']) &&
            trim($doc['path']) !== ''
        ) {

            return ltrim(
                trim($doc['path']),
                '/'
            );
        }

        /*
         * Kalau path tidak ada, coba ambil dari URL lama.
         */
        if (
            !isset($doc['url']) ||
            !is_string($doc['url']) ||
            trim($doc['url']) === ''
        ) {
            return null;
        }

        $url = trim($doc['url']);

        /*
         * URL S3 Supabase kita menggunakan:
         *
         * endpoint/storage/v1/s3/bucket/path
         *
         * Contoh:
         * https://...storage.supabase.co/storage/v1/s3/
         * moneflo-storage/transaksi/images/file.png
         */
        $endpoint = rtrim(
            (string) env('AWS_ENDPOINT'),
            '/'
        );

        $bucket = trim(
            (string) env('AWS_BUCKET'),
            '/'
        );

        if (
            $endpoint !== '' &&
            $bucket !== ''
        ) {

            $prefix =
                $endpoint
                . '/'
                . $bucket
                . '/';

            if (str_starts_with($url, $prefix)) {

                $path = substr(
                    $url,
                    strlen($prefix)
                );

                $path = urldecode(
                    ltrim($path, '/')
                );

                if ($path !== '') {
                    return $path;
                }
            }
        }

        /*
         * Fallback tambahan:
         * coba parse URL berdasarkan struktur path.
         */
        try {

            $parsed = parse_url($url);

            $urlPath = $parsed['path'] ?? '';

            if ($urlPath !== '') {

                $marker =
                    '/storage/v1/s3/'
                    . $bucket
                    . '/';

                $position = strpos(
                    $urlPath,
                    $marker
                );

                if ($position !== false) {

                    $path = substr(
                        $urlPath,
                        $position + strlen($marker)
                    );

                    $path = urldecode(
                        ltrim($path, '/')
                    );

                    if ($path !== '') {
                        return $path;
                    }
                }
            }

        } catch (\Throwable $e) {

            Log::warning(
                'Gagal membaca path dari URL dokumen lama',
                [
                    'message' => $e->getMessage()
                ]
            );
        }

        return null;
    }


    /**
     * Ambil organisasi user.
     */
    private function getOrganisasi(
        $user,
        ?int $id
    ): ?Organisasi {

        if (
            $user->isAdmin() &&
            $id
        ) {
            return Organisasi::find($id);
        }

        if ($id) {
            return $user
                ->organisasi()
                ->find($id);
        }

        return $user->isAdmin()
            ? Organisasi::first()
            : $user->organisasi()->first();
    }


    /**
     * Pastikan user punya akses transaksi.
     */
    private function authorizeTransaksiAccess(
        $user,
        Transaksi $t
    ): void {

        if ($user->isAdmin()) {
            return;
        }

        $isMember = AnggotaOrganisasi::where(
            'user_id',
            $user->id
        )
            ->where(
                'organisasi_id',
                $t->organisasi_id
            )
            ->exists();

        if (!$isMember) {
            abort(
                403,
                'Akses ditolak'
            );
        }
    }


    /**
     * Pastikan user adalah bendahara/ketua.
     */
    private function authorizeBendaharaOrKetua(
        $user,
        int $organisasiId
    ): void {

        if ($user->isAdmin()) {
            return;
        }

        $anggota = AnggotaOrganisasi::where(
            'user_id',
            $user->id
        )
            ->where(
                'organisasi_id',
                $organisasiId
            )
            ->first();

        if (
            !$anggota ||
            !in_array(
                $anggota->role,
                ['ketua', 'bendahara']
            )
        ) {
            abort(
                403,
                'Hanya ketua atau bendahara yang bisa menyetujui/menolak transaksi'
            );
        }
    }
}
