<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Model BandingOrganisasi — pengajuan banding dari organisasi yang tersuspend.
 */
class BandingOrganisasi extends Model
{
    use HasFactory;

    protected $table = 'banding_organisasi';

    protected $fillable = [
        'organisasi_id',
        'user_id',
        'message',
        'evidence_path',
        'status',
        'admin_note',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /* ──────────────────────────────────────
     * RELASI
     * ────────────────────────────────────── */

    public function organisasi()
    {
        return $this->belongsTo(Organisasi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* ──────────────────────────────────────
     * SCOPES
     * ────────────────────────────────────── */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * URL bukti banding.
     *
     * Mendukung:
     * - file baru berupa path Supabase S3
     * - file lama berupa path
     * - file lama berupa URL Supabase S3
     * - URL eksternal
     *
     * Signed URL berlaku 60 menit.
     */
    public function getEvidenceUrlAttribute(): ?string
    {
        if (!$this->evidence_path) {
            return null;
        }

        $storedValue = trim(
            (string) $this->evidence_path
        );

        if ($storedValue === '') {
            return null;
        }

        /*
         * Cari path file terlebih dahulu.
         */
        $path = $this->resolveEvidencePath(
            $storedValue
        );

        /*
         * Kalau path ditemukan, buat signed URL.
         */
        if ($path) {

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
                        'path'    => $path,
                        'message' => $e->getMessage(),
                    ]
                );

                return null;
            }
        }

        /*
         * Kalau bukan path storage dan merupakan URL
         * eksternal, tetap kembalikan URL tersebut.
         */
        if (
            filter_var(
                $storedValue,
                FILTER_VALIDATE_URL
            )
        ) {
            return $storedValue;
        }

        return null;
    }

    /**
     * Ambil path file dari evidence_path.
     *
     * Mendukung path baru dan URL lama.
     */
    private function resolveEvidencePath(
        string $storedValue
    ): ?string {

        /*
         * Kalau bukan URL, anggap sebagai path S3.
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

        $endpoint = rtrim(
            (string) env('AWS_ENDPOINT'),
            '/'
        );

        $bucket = trim(
            (string) env('AWS_BUCKET'),
            '/'
        );

        if (
            $endpoint === '' ||
            $bucket === ''
        ) {
            return null;
        }

        /*
         * URL S3 Supabase:
         *
         * endpoint/bucket/banding-evidence/file.png
         */
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

        /*
         * Fallback URL Supabase Storage REST.
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
