<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Membuat tabel banding_organisasi untuk pengajuan banding suspend.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banding_organisasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisasi_id')->constrained('organisasi')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Pengaju banding
            $table->text('message');                                                  // Pesan/alasan banding
            $table->string('evidence_path')->nullable();                              // Path file bukti (foto)
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();                                   // Catatan admin saat resolve
            $table->timestamp('resolved_at')->nullable();                            // Waktu diselesaikan
            $table->timestamps();

            $table->index(['organisasi_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banding_organisasi');
    }
};
