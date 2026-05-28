<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

// Route Darurat: Download semua isi storage/app/public dalam bentuk ZIP
Route::get('/download-semua-data', function () {
    $zip = new \ZipArchive;
    $zipFileName = 'backup_storage_' . date('Y-m-d_His') . '.zip';
    $zipPath = storage_path($zipFileName);

    // Membuka/Membuat file ZIP
    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
        // Mengambil semua file dari folder 'public' (storage/app/public)
        $files = Storage::disk('public')->allFiles();

        foreach ($files as $file) {
            // Menambahkan file ke dalam ZIP
            // Menggunakan path absolut untuk memastikan file ditemukan
            $zip->addFile(storage_path('app/public/' . $file), $file);
        }
        $zip->close();
    }

    // Cek apakah file zip berhasil dibuat
    if (file_exists($zipPath)) {
        // Mengirim file ke browser dan menghapus file setelah selesai diunduh
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    return "Gagal membuat file ZIP. Pastikan ada file di dalam folder storage.";
});