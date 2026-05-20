<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tambah kolom docs (JSON) ke tabel transaksi
 * untuk menyimpan bukti foto/file transaksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            // Kolom JSON untuk menyimpan array bukti transaksi
            // Format: [{ name, type, dataUrl }]
            $table->json('docs')->nullable()->after('attachment')
                  ->comment('Array bukti transaksi (JSON): [{name, type, dataUrl}]');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn('docs');
        });
    }
};
