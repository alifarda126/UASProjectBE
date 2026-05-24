<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tabel pivot antara users dan organisasi.
 * Menyimpan keanggotaan beserta role dalam organisasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggota_organisasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('organisasi_id')->constrained('organisasi')->onDelete('cascade');
            $table->enum('role', ['ketua', 'bendahara', 'sekretaris', 'anggota'])->default('anggota');
            $table->date('joined_at')->nullable();                         // Tanggal bergabung
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Satu user hanya bisa jadi satu anggota dalam satu organisasi
            $table->unique(['user_id', 'organisasi_id']);
            $table->index(['organisasi_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota_organisasi');
    }
};
