<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Menambahkan kolom suspend ke tabel organisasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organisasi', function (Blueprint $table) {
            $table->boolean('is_suspended')->default(false)->after('is_active');   // Status suspend
            $table->text('suspended_reason')->nullable()->after('is_suspended');   // Pesan/alasan suspend
            $table->timestamp('suspended_at')->nullable()->after('suspended_reason'); // Waktu suspend
        });
    }

    public function down(): void
    {
        Schema::table('organisasi', function (Blueprint $table) {
            $table->dropColumn(['is_suspended', 'suspended_reason', 'suspended_at']);
        });
    }
};
