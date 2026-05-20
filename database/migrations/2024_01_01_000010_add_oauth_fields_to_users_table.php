<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tambahkan kolom OAuth & profil ke tabel users yang sudah ada.
 * Menggunakan addColumn agar aman dijalankan pada database yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kolom password menjadi nullable (untuk user OAuth yang tidak punya password)
            $table->string('password')->nullable()->change();

            // Kolom profil & avatar
            $table->string('avatar')->nullable()->after('email');

            // OAuth provider info
            $table->string('provider')->nullable()->after('avatar');         // 'google', 'github', dll.
            $table->string('provider_id')->nullable()->after('provider');    // ID dari provider
            $table->text('provider_token')->nullable()->after('provider_id'); // Access token dari provider

            // Role & status
            $table->enum('role', ['admin', 'user'])->default('user')->after('provider_token');
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar',
                'provider',
                'provider_id',
                'provider_token',
                'role',
                'is_active',
                'last_login_at',
            ]);
            $table->string('password')->nullable(false)->change();
        });
    }
};
