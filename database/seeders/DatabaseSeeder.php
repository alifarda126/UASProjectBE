<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder — Orkestrasi semua seeder aplikasi MoneFlo.
 * Urutan penting: Admin → Organisasi → Transaksi → Agenda → Notification
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,        // 1. User admin & contoh
            OrganisasiSeeder::class,   // 2. Organisasi & keanggotaan
            TransaksiSeeder::class,    // 3. Transaksi keuangan
            AgendaSeeder::class,       // 4. Agenda kegiatan
            NotificationSeeder::class, // 5. Notifikasi
        ]);

        $this->command->info('');
        $this->command->info('🎉 Semua seeder berhasil dijalankan!');
        $this->command->info('   Admin: moneflosupp@gmail.com / Admin123');
    }
}
