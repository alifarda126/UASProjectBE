<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_anggota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisasi_id')
                  ->constrained('organisasi')
                  ->cascadeOnDelete();
            $table->string('name');
            $table->string('nim')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_anggota');
    }
};
