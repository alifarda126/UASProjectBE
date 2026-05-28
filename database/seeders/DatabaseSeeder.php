<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder — Orkestrasi semua seeder aplikasi MoneFlo.
 * Urutan penting: User → Organisasi → Transaksi → Agenda → Notification
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,         // 1. Semua user (admin + user biasa)
            OrganisasiSeeder::class,   // 2. Organisasi & keanggotaan
            TransaksiSeeder::class,    // 3. Transaksi keuangan
            AgendaSeeder::class,       // 4. Agenda kegiatan
            NotificationSeeder::class, // 5. Notifikasi
        ]);

        $this->command->info('');
        $this->command->info('🎉 Semua seeder berhasil dijalankan!');
        $this->command->info('   Admin : moneflosupp@gmail.com  (password di backup)');
        $this->command->info('   User  : kosmail012@gmail.com   (password di backup)');
    }
}
