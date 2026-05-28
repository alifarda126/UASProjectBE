<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * NotificationSeeder — Data notifikasi dari backup MoneFlo.
 */
class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('notifications')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::table('notifications')->insert([
            [
                'id'         => 1,
                'user_id'    => 2,
                'title'      => 'Organisasi Anda Disuspend',
                'message'    => 'Organisasi "Futsal Club" telah disuspend oleh admin. Alasan: test',
                'type'       => 'error',
                'icon'       => 'fa-ban',
                'link'       => null,
                'is_read'    => 1,
                'read_at'    => '2026-05-26 17:35:21',
                'created_at' => '2026-05-26 06:11:47',
                'updated_at' => '2026-05-26 17:35:21',
            ],
            [
                'id'         => 2,
                'user_id'    => 2,
                'title'      => '✅ Banding Diterima',
                'message'    => "Banding organisasi \"Futsal Club\" telah DITERIMA. Suspend telah dicabut.\n\nCatatan Admin: ok",
                'type'       => 'success',
                'icon'       => 'fa-check-circle',
                'link'       => '/dashboard/pengaturan',
                'is_read'    => 1,
                'read_at'    => '2026-05-26 17:35:21',
                'created_at' => '2026-05-26 06:13:45',
                'updated_at' => '2026-05-26 17:35:21',
            ],
            [
                'id'         => 3,
                'user_id'    => 1,
                'title'      => 'Pengumuman Sistem',
                'message'    => 'Dalam Masa Perbaikan!',
                'type'       => 'info',
                'icon'       => 'fa-bullhorn',
                'link'       => null,
                'is_read'    => 0,
                'read_at'    => null,
                'created_at' => '2026-05-26 06:16:56',
                'updated_at' => '2026-05-26 06:16:56',
            ],
            [
                'id'         => 4,
                'user_id'    => 2,
                'title'      => 'Pengumuman Sistem',
                'message'    => 'Dalam Masa Perbaikan!',
                'type'       => 'info',
                'icon'       => 'fa-bullhorn',
                'link'       => null,
                'is_read'    => 1,
                'read_at'    => '2026-05-26 17:35:21',
                'created_at' => '2026-05-26 06:16:57',
                'updated_at' => '2026-05-26 17:35:21',
            ],
            [
                'id'         => 5,
                'user_id'    => 2,
                'title'      => 'Organisasi Anda Disuspend',
                'message'    => 'Organisasi "Futsal Club" telah disuspend oleh admin. Alasan: test',
                'type'       => 'error',
                'icon'       => 'fa-ban',
                'link'       => null,
                'is_read'    => 1,
                'read_at'    => '2026-05-26 17:35:21',
                'created_at' => '2026-05-26 08:14:45',
                'updated_at' => '2026-05-26 17:35:21',
            ],
            [
                'id'         => 6,
                'user_id'    => 2,
                'title'      => '✅ Banding Diterima',
                'message'    => 'Banding organisasi "Futsal Club" telah DITERIMA. Suspend telah dicabut.',
                'type'       => 'success',
                'icon'       => 'fa-check-circle',
                'link'       => '/dashboard/pengaturan',
                'is_read'    => 1,
                'read_at'    => '2026-05-26 17:35:21',
                'created_at' => '2026-05-26 08:16:13',
                'updated_at' => '2026-05-26 17:35:21',
            ],
        ]);

        $this->command->info('✅ NotificationSeeder: 6 notifikasi berhasil di-seed.');
    }
}
