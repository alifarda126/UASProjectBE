<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_anggaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisasi_id')
                  ->constrained('organisasi')
                  ->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('progress')->default(0); // 0–100
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_anggaran');
    }
};
