<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tabel transaksi keuangan organisasi (pemasukan & pengeluaran).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisasi_id')->constrained('organisasi')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');             // Pembuat transaksi
            $table->foreignId('approved_by')->nullable()->constrained('users'); // User yang approve

            $table->enum('type', ['pemasukan', 'pengeluaran']);             // Jenis transaksi
            $table->string('category');                                     // Kategori (Iuran, Sponsor, dll.)
            $table->string('description');                                  // Deskripsi transaksi
            $table->decimal('amount', 15, 2);                               // Nominal (rupiah)
            $table->date('date');                                           // Tanggal transaksi
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();                              // Catatan tambahan
            $table->string('attachment')->nullable();                       // File bukti transaksi
            $table->timestamp('approved_at')->nullable();                   // Waktu approval
            $table->timestamps();
            $table->softDeletes();

            // Index untuk filter yang sering digunakan
            $table->index(['organisasi_id', 'type']);
            $table->index(['organisasi_id', 'status']);
            $table->index(['organisasi_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
