<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organisasi', function (Blueprint $table) {
            $table->integer('dues_interval')->default(7)->after('is_active');
            $table->decimal('dues_amount', 15, 2)->default(15000)->after('dues_interval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organisasi', function (Blueprint $table) {
            $table->dropColumn(['dues_interval', 'dues_amount']);
        });
    }
};
