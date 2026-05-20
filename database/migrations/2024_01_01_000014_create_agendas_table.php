<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tabel agenda/jadwal kegiatan organisasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisasi_id')->constrained('organisasi')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');             // Pembuat agenda
            $table->string('title');                                        // Judul agenda
            $table->text('description')->nullable();                        // Deskripsi agenda
            $table->string('location')->nullable();                         // Lokasi kegiatan
            $table->datetime('start_at');                                   // Waktu mulai
            $table->datetime('end_at')->nullable();                         // Waktu selesai
            $table->enum('type', ['rapat', 'workshop', 'gathering', 'seminar', 'lainnya'])->default('lainnya');
            $table->enum('status', ['upcoming', 'ongoing', 'done', 'cancelled'])->default('upcoming');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organisasi_id', 'start_at']);
            $table->index(['organisasi_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};
