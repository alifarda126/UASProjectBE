<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tabel notifikasi untuk setiap user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');                                        // Judul notifikasi
            $table->text('message');                                        // Isi notifikasi
            $table->string('type')->default('info');                        // 'info', 'success', 'warning', 'error'
            $table->string('icon')->nullable();                             // Ikon Font Awesome
            $table->string('link')->nullable();                             // Link aksi notifikasi
            $table->boolean('is_read')->default(false);                     // Status baca
            $table->timestamp('read_at')->nullable();                       // Waktu dibaca
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
