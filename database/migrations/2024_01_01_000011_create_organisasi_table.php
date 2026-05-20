<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Membuat tabel organisasi untuk manajemen organisasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisasi', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                        // Nama organisasi
            $table->string('code')->unique();                              // Kode unik organisasi (e.g. MFTC2024)
            $table->text('description')->nullable();                       // Deskripsi singkat
            $table->string('type')->default('Kemahasiswaan');              // Jenis organisasi
            $table->string('logo')->nullable();                            // URL logo organisasi
            $table->string('email')->nullable();                           // Email resmi organisasi
            $table->string('phone')->nullable();                           // Nomor telepon
            $table->string('address')->nullable();                         // Alamat
            $table->boolean('is_active')->default(true);                   // Status aktif
            $table->foreignId('created_by')->constrained('users');         // User pembuat
            $table->timestamps();
            $table->softDeletes();

            // Index untuk performa query
            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisasi');
    }
};
