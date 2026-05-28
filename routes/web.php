<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

// Route Darurat Pemindahan Data ke Storj
Route::get('/pindah-data-rahasia', function () {
    // 1. Ambil semua file dari folder storage lokal server Clever Cloud
    $files = Storage::disk('local')->allFiles('public');
    
    $pindah = 0;
    foreach ($files as $file) {
        // Baca isi file-nya
        $content = Storage::disk('local')->get($file);
        
        // Bersihkan path (menghilangkan kata 'public/' di depannya agar rapi di Storj)
        $cleanPath = str_replace('public/', '', $file);
        
        // 2. Upload langsung ke Storj (disk s3)
        Storage::disk('s3')->put($cleanPath, $content);
        $pindah++;
    }

    return "Mantap! " . $pindah . " file berhasil dipindahkan ke Storj.";
});