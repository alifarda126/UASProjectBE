<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * UploadController — Upload file bukti transaksi ke Supabase S3.
 *
 * Batasan:
 *   Gambar (JPG/PNG/WEBP) : maks. 2 MB
 *   Dokumen (PDF/DOC/DOCX) : maks. 5 MB
 *   Total file per request : maks. 5 file
 */
class UploadController extends Controller
{
    private const IMAGE_MAX_BYTES = 2 * 1024 * 1024; // 2 MB
    private const DOC_MAX_BYTES   = 5 * 1024 * 1024; // 5 MB
    private const MAX_FILES       = 5;

    private const IMAGE_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];

    private const DOC_MIMES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    /**
     * POST /api/upload/doc
     *
     * Upload satu atau lebih file bukti transaksi.
     */
    public function uploadDocs(Request $request): JsonResponse
    {
        $request->validate([
            'files'   => 'required|array|max:' . self::MAX_FILES,
            'files.*' => 'required|file',
        ], [
            'files.required' => 'Tidak ada file yang dikirim.',
            'files.max'      => 'Maksimal ' . self::MAX_FILES . ' file per upload.',
        ]);

        $disk = Storage::disk('s3');

        $results = [];
        $errors  = [];

        foreach ($request->file('files', []) as $file) {

            $mime = $file->getMimeType();
            $size = $file->getSize();
            $name = $file->getClientOriginalName();

            /*
             * Validasi MIME type
             */
            $isImage = in_array($mime, self::IMAGE_MIMES, true);
            $isDoc   = in_array($mime, self::DOC_MIMES, true);

            if (!$isImage && !$isDoc) {
                $errors[] =
                    "\"$name\": format tidak didukung. Gunakan JPG, PNG, WEBP, PDF, DOC, atau DOCX.";

                continue;
            }

            /*
             * Validasi ukuran file
             */
            $maxBytes = $isImage
                ? self::IMAGE_MAX_BYTES
                : self::DOC_MAX_BYTES;

            if ($size > $maxBytes) {

                $maxLabel = $isImage
                    ? '2MB'
                    : '5MB';

                $errors[] =
                    "\"$name\": ukuran file melebihi batas $maxLabel untuk "
                    . ($isImage ? 'gambar' : 'dokumen') . ".";

                continue;
            }

            /*
             * Tentukan folder berdasarkan tipe file
             */
            $folder = $isImage
                ? 'transaksi/images'
                : 'transaksi/docs';

            /*
             * Buat nama file aman dan unik
             */
            $originalExtension = strtolower(
                $file->getClientOriginalExtension()
            );

            $originalName = pathinfo(
                $name,
                PATHINFO_FILENAME
            );

            $slugName = Str::slug($originalName);

            if ($slugName === '') {
                $slugName = 'file';
            }

            $safeName =
                $slugName
                . '-'
                . Str::random(8)
                . '.'
                . $originalExtension;

            $path = $folder . '/' . $safeName;

            /*
             * Upload ke Supabase S3.
             */
            try {

                $stream = fopen(
                    $file->getRealPath(),
                    'rb'
                );

                if ($stream === false) {
                    throw new \RuntimeException(
                        'File temporary tidak dapat dibuka.'
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

                    Log::error(
                        'Upload S3 gagal: Storage::put mengembalikan false',
                        [
                            'file' => $name,
                            'path' => $path,
                            'disk' => 's3',
                        ]
                    );

                    $errors[] =
                        "\"$name\": gagal diupload, coba lagi.";

                    continue;
                }

            } catch (\Throwable $e) {

                Log::error(
                    'Upload S3 gagal',
                    [
                        'file'      => $name,
                        'path'      => $path,
                        'disk'      => 's3',
                        'message'   => $e->getMessage(),
                        'exception' => get_class($e),
                    ]
                );

                $errors[] =
                    "\"$name\": gagal diupload: "
                    . $e->getMessage();

                continue;
            }

            /*
             * Pastikan file benar-benar ada di S3.
             */
            try {

                $exists = $disk->exists($path);

                if (!$exists) {

                    Log::error(
                        'Upload S3 tidak ditemukan setelah write',
                        [
                            'file' => $name,
                            'path' => $path,
                        ]
                    );

                    $errors[] =
                        "\"$name\": upload berhasil tetapi file tidak ditemukan di storage.";

                    continue;
                }

            } catch (\Throwable $e) {

                Log::error(
                    'Gagal memverifikasi file S3',
                    [
                        'file'      => $name,
                        'path'      => $path,
                        'message'   => $e->getMessage(),
                        'exception' => get_class($e),
                    ]
                );

                $errors[] =
                    "\"$name\": file berhasil disimpan tetapi gagal diverifikasi.";

                continue;
            }

            /*
             * Buat SIGNED URL.
             *
             * Jangan menggunakan:
             *
             *     $disk->url($path)
             *
             * karena bucket Supabase S3 tidak dapat diakses
             * secara public tanpa signature.
             *
             * temporaryUrl() menghasilkan URL yang memiliki
             * signature sehingga browser dapat membuka file.
             *
             * URL berlaku selama 60 menit.
             */
            try {

                $url = $disk->temporaryUrl(
                    $path,
                    now()->addMinutes(60)
                );

            } catch (\Throwable $e) {

                Log::error(
                    'Gagal membuat signed URL S3',
                    [
                        'file'      => $name,
                        'path'      => $path,
                        'message'   => $e->getMessage(),
                        'exception' => get_class($e),
                    ]
                );

                $errors[] =
                    "\"$name\": file berhasil disimpan tetapi signed URL gagal dibuat.";

                continue;
            }

            /*
             * Simpan hasil upload.
             */
            $results[] = [
                'url'       => $url,
                'path'      => $path,
                'name'      => $name,
                'size'      => $size,
                'mime_type' => $mime,
                'is_image'  => $isImage,
            ];
        }

        /*
         * Semua file gagal.
         */
        if (!empty($errors) && empty($results)) {

            return response()->json([
                'message' => 'Semua file gagal diupload.',
                'errors'  => $errors,
            ], 422);
        }

        /*
         * Sebagian atau semua berhasil.
         */
        return response()->json([
            'message' => count($results)
                . ' file berhasil diupload.'
                . (!empty($errors)
                    ? ' Beberapa file gagal.'
                    : ''),

            'data'   => $results,
            'errors' => $errors,

        ], 201);
    }

    /**
     * DELETE /api/upload/doc
     *
     * Hapus file dari S3 berdasarkan path.
     */
    public function deleteDocs(Request $request): JsonResponse
    {
        $request->validate([
            'paths'   => 'required|array',
            'paths.*' => 'required|string',
        ]);

        $disk = Storage::disk('s3');

        $deleted = 0;

        foreach ($request->input('paths', []) as $path) {

            /*
             * Keamanan:
             * hanya izinkan path dari folder aplikasi.
             */
            if (
                !str_starts_with($path, 'transaksi/')
                && !str_starts_with($path, 'avatars/')
                && !str_starts_with($path, 'logos/')
            ) {
                continue;
            }

            try {

                if ($disk->exists($path)) {

                    if ($disk->delete($path)) {
                        $deleted++;
                    }
                }

            } catch (\Throwable $e) {

                Log::error(
                    'Gagal menghapus file S3',
                    [
                        'path'      => $path,
                        'message'   => $e->getMessage(),
                        'exception' => get_class($e),
                    ]
                );
            }
        }

        return response()->json([
            'message' => "$deleted file berhasil dihapus.",
        ]);
    }
}
