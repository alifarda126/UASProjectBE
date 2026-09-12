<?php

namespace App\Http\Controllers;

use App\Models\BandingOrganisasi;
use App\Models\Organisasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $orgIds = $user->organisasi()
            ->pluck('organisasi.id');

        $bandings = BandingOrganisasi::whereIn(
            'organisasi_id',
            $orgIds
        )
            ->with(
                'organisasi:id,name,type,is_suspended,suspended_reason'
            )
            ->latest()
            ->get()
            ->map(function ($b) {

                return [
                    'id' => $b->id,

                    'organisasi' => $b->organisasi
                        ? [
                            'id' =>
                                $b->organisasi->id,

                            'name' =>
                                $b->organisasi->name,

                            'type' =>
                                $b->organisasi->type,

                            'is_suspended' =>
                                $b->organisasi->is_suspended,

                            'suspended_reason' =>
                                $b->organisasi->suspended_reason,
                        ]
                        : null,

                    'message' =>
                        $b->message,

                    /*
                     * Generate signed URL baru setiap kali
                     * data banding dibaca.
                     *
                     * Ini membuat file lama tetap bisa preview.
                     */
                    'evidence_url' =>
                        $this->getEvidenceUrl(
                            $b->evidence_path
                        ),

                    /*
                     * Path tetap dikirim agar frontend/backend
                     * dapat menggunakannya untuk delete.
                     */
                    'evidence_path' =>
                        $this->resolveEvidencePath(
                            $b->evidence_path
                        ),

                    'status' =>
                        $b->status,

                    'admin_note' =>
                        $b->admin_note,

                    'resolved_at' =>
                        $b->resolved_at?->toISOString(),

                    'created_at' =>
                        $b->created_at?->toISOString(),
                ];
            });

        return response()->json([
            'data' => $bandings
        ]);
    }


    /** Ajukan banding baru dengan upload bukti */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'organisasi_id' =>
                'required|integer|exists:organisasi,id',

            'message' =>
                'required|string|max:2000',

            'evidence' =>
                'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $user = $request->user();
        $orgId = $request->organisasi_id;

        // Pastikan user adalah anggota organisasi tersebut
        $isMember = $user
            ->organisasi()
            ->where(
                'organisasi.id',
                $orgId
            )
            ->exists();

        if (!$isMember) {

            return response()->json([
                'message' =>
                    'Anda bukan anggota organisasi ini'
            ], 403);
        }

        // Pastikan organisasi sedang tersuspend
        $organisasi = Organisasi::findOrFail(
            $orgId
        );

        if (
            !$organisasi->is_suspended &&
            $organisasi->is_active !== false
        ) {

            return response()->json([
                'message' =>
                    'Organisasi ini dalam keadaan aktif dan tidak memerlukan banding'
            ], 422);
        }

        // Cek apakah sudah ada banding pending
        $existingPending = BandingOrganisasi::where(
            'organisasi_id',
            $orgId
        )
            ->where(
                'status',
                'pending'
            )
            ->exists();

        if ($existingPending) {

            return response()->json([
                'message' =>
                    'Sudah ada banding yang sedang menunggu proses untuk organisasi ini'
            ], 422);
        }


        /*
         * Upload bukti ke Supabase S3.
         *
         * Database hanya menyimpan PATH.
         * Jangan menyimpan signed URL.
         */
        $evidencePath = null;

        if ($request->hasFile('evidence')) {

            $file = $request->file('evidence');

            $extension = strtolower(
                $file->getClientOriginalExtension()
            );

            $safeName =
                'banding-'
                . $user->id
                . '-'
                . Str::random(20)
                . '.'
                . $extension;

            $path =
                'banding-evidence/'
                . $safeName;

            try {

                $disk = Storage::disk('s3');

                $stream = fopen(
                    $file->getRealPath(),
                    'rb'
                );

                if ($stream === false) {
                    throw new \RuntimeException(
                        'File temporary bukti banding tidak dapat dibuka.'
                    );
                }

                try {

                    $uploaded = $disk->put(
                        $path,
                        $stream
                    );

                } finally {

                    fclose($stream);
                }

                if (!$uploaded) {
                    throw new \RuntimeException(
                        'Upload bukti banding gagal.'
                    );
                }

                /*
                 * Simpan PATH, bukan URL.
                 */
                $evidencePath = $path;

            } catch (\Throwable $e) {

                Log::error(
                    'Upload bukti banding S3 gagal',
                    [
                        'user_id'      => $user->id,
                        'organisasi_id'=> $orgId,
                        'message'      => $e->getMessage(),
                        'exception'    => get_class($e),
                    ]
                );

                return response()->json([
                    'message' =>
                        'Gagal mengupload bukti banding: '
                        . $e->getMessage(),
                ], 422);
            }
        }


        /*
         * Simpan banding.
         */
        $banding = BandingOrganisasi::create([
            'organisasi_id' =>
                $orgId,

            'user_id' =>
                $user->id,

            'message' =>
                $request->message,

            'evidence_path' =>
                $evidencePath,

            'status' =>
                'pending',
        ]);


        /*
         * Generate signed URL untuk response.
         */
        $evidenceUrl =
            $this->getEvidenceUrl(
                $banding->evidence_path
            );

        return response()->json([
            'message' =>
                'Banding berhasil diajukan. Admin akan memeriksa pengajuan Anda.',

            'data' => [
                'id' =>
                    $banding->id,

                'status' =>
                    $banding->status,

                'evidence_url' =>
                    $evidenceUrl,

                'evidence_path' =>
                    $this->resolveEvidencePath(
                        $banding->evidence_path
                    ),

                'created_at' =>
                    $banding->created_at
                        ->toISOString(),
            ],
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */


    /**
     * Buat signed URL untuk bukti banding.
     *
     * Berlaku 60 menit.
     */
    private function getEvidenceUrl(
        ?string $storedValue
    ): ?string {

        $path = $this->resolveEvidencePath(
            $storedValue
        );

        if (!$path) {
            return null;
        }

        try {

            return Storage::disk('s3')
                ->temporaryUrl(
                    $path,
                    now()->addMinutes(60)
                );

        } catch (\Throwable $e) {

            Log::warning(
                'Gagal membuat signed URL bukti banding',
                [
                    'path' =>
                        $path,

                    'message' =>
                        $e->getMessage(),
                ]
            );

            return null;
        }
    }


    /**
     * Ambil PATH storage dari data lama maupun baru.
     *
     * Data baru:
     * banding-evidence/file.png
     *
     * Data lama:
     * https://...storage.supabase.co/.../moneflo-storage/banding-evidence/file.png
     */
    private function resolveEvidencePath(
        ?string $storedValue
    ): ?string {

        if (!$storedValue) {
            return null;
        }

        $storedValue = trim(
            $storedValue
        );

        if ($storedValue === '') {
            return null;
        }


        /*
         * Jika bukan URL, anggap sebagai path.
         */
        if (
            !filter_var(
                $storedValue,
                FILTER_VALIDATE_URL
            )
        ) {

            $path = ltrim(
                $storedValue,
                '/'
            );

            if (
                str_starts_with(
                    $path,
                    'banding-evidence/'
                )
            ) {
                return $path;
            }

            return null;
        }


        /*
         * URL S3 Supabase:
         *
         * endpoint/bucket/banding-evidence/file.png
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

            if (
                str_starts_with(
                    $storedValue,
                    $prefix
                )
            ) {

                $path = urldecode(
                    substr(
                        $storedValue,
                        strlen($prefix)
                    )
                );

                if (
                    str_starts_with(
                        $path,
                        'banding-evidence/'
                    )
                ) {
                    return ltrim(
                        $path,
                        '/'
                    );
                }
            }
        }


        /*
         * Fallback untuk URL Supabase Storage REST.
         */
        try {

            $parsed = parse_url(
                $storedValue
            );

            $urlPath =
                $parsed['path'] ?? '';

            $markers = [
                '/storage/v1/object/public/' . $bucket . '/',
                '/storage/v1/object/sign/' . $bucket . '/',
                '/storage/v1/object/authenticated/' . $bucket . '/',
            ];

            foreach ($markers as $marker) {

                $position = strpos(
                    $urlPath,
                    $marker
                );

                if ($position !== false) {

                    $path = urldecode(
                        substr(
                            $urlPath,
                            $position + strlen($marker)
                        )
                    );

                    if (
                        str_starts_with(
                            $path,
                            'banding-evidence/'
                        )
                    ) {
                        return ltrim(
                            $path,
                            '/'
                        );
                    }
                }
            }

        } catch (\Throwable $e) {

            Log::warning(
                'Gagal membaca path bukti banding lama',
                [
                    'message' =>
                        $e->getMessage(),
                ]
            );
        }

        return null;
    }
}
