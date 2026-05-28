<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * UploadController — Upload file bukti transaksi ke S3.
 *
 * Batasan:
 *   Gambar (JPG/PNG/WEBP) : maks. 2 MB
 *   Dokumen (PDF/DOC/DOCX) : maks. 5 MB
 *   Total file per request : maks. 5 file
 */
class UploadController extends Controller
{
    // Limit ukuran per tipe (dalam bytes)
    private const IMAGE_MAX_BYTES = 2 * 1024 * 1024;   // 2 MB
    private const DOC_MAX_BYTES   = 5 * 1024 * 1024;   // 5 MB
    private const MAX_FILES       = 5;

    private const IMAGE_MIMES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    private const DOC_MIMES   = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    /**
     * POST /api/upload/doc
     * Upload satu atau lebih file bukti transaksi.
     * Mengembalikan array URL file yang tersimpan di S3.
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

        $disk    = config('filesystems.default');
        $results = [];
        $errors  = [];

        foreach ($request->file('files', []) as $file) {
            $mime = $file->getMimeType();
            $size = $file->getSize();
            $name = $file->getClientOriginalName();

            // Validasi MIME type
            $isImage = in_array($mime, self::IMAGE_MIMES);
            $isDoc   = in_array($mime, self::DOC_MIMES);

            if (!$isImage && !$isDoc) {
                $errors[] = "\"$name\": format tidak didukung. Gunakan JPG, PNG, WEBP, PDF, DOC, atau DOCX.";
                continue;
            }

            // Validasi ukuran sesuai tipe
            $maxBytes = $isImage ? self::IMAGE_MAX_BYTES : self::DOC_MAX_BYTES;
            if ($size > $maxBytes) {
                $maxLabel = $isImage ? '2MB' : '5MB';
                $errors[] = "\"$name\": ukuran file melebihi batas $maxLabel untuk " . ($isImage ? 'gambar' : 'dokumen') . ".";
                continue;
            }

            // Tentukan subfolder berdasarkan tipe
            $folder = $isImage ? 'transaksi/images' : 'transaksi/docs';

            // Buat nama file unik agar tidak bentrok
            $ext      = $file->getClientOriginalExtension();
            $safeName = Str::slug(pathinfo($name, PATHINFO_FILENAME)) . '-' . Str::random(8) . '.' . $ext;
            $path     = $file->storeAs($folder, $safeName, ['disk' => $disk, 'visibility' => 'public']);

            if (!$path) {
                $errors[] = "\"$name\": gagal diupload, coba lagi.";
                continue;
            }

            $results[] = [
                'url'       => Storage::disk($disk)->url($path),
                'path'      => $path,
                'name'      => $name,
                'size'      => $size,
                'mime_type' => $mime,
                'is_image'  => $isImage,
            ];
        }

        if (!empty($errors) && empty($results)) {
            return response()->json([
                'message' => 'Semua file gagal diupload.',
                'errors'  => $errors,
            ], 422);
        }

        return response()->json([
            'message' => count($results) . ' file berhasil diupload.' . (!empty($errors) ? ' Beberapa file gagal.' : ''),
            'data'    => $results,
            'errors'  => $errors,
        ], 201);
    }

    /**
     * DELETE /api/upload/doc
     * Hapus file dari S3 berdasarkan path.
     */
    public function deleteDocs(Request $request): JsonResponse
    {
        $request->validate([
            'paths'   => 'required|array',
            'paths.*' => 'required|string',
        ]);

        $disk    = config('filesystems.default');
        $deleted = 0;

        foreach ($request->input('paths', []) as $path) {
            // Keamanan: pastikan path tidak keluar dari folder transaksi
            if (!str_starts_with($path, 'transaksi/') && !str_starts_with($path, 'avatars/') && !str_starts_with($path, 'logos/')) {
                continue;
            }
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                $deleted++;
            }
        }

        return response()->json(['message' => "$deleted file berhasil dihapus."]);
    }
}
