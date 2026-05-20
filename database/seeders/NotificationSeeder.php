<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder: Notifikasi contoh untuk user.
 */
class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'moneflosupport@gmail.com')->first();
        $user1 = User::where('email', 'budi.santoso@example.com')->first();
        $user2 = User::where('email', 'siti.rahayu@example.com')->first();
        $user3 = User::where('email', 'ahmad.fauzi@example.com')->first();

        if (!$admin) {
            $this->command->warn('AdminSeeder belum dijalankan. Skip NotificationSeeder.');
            return;
        }

        $notifications = [
            [
                'user_id' => $admin->id,
                'title'   => 'Selamat Datang di MoneFlo!',
                'message' => 'Akun admin Anda telah berhasil dibuat. Mulai kelola keuangan organisasi Anda sekarang.',
                'type'    => 'success',
                'icon'    => 'fa-check-circle',
                'is_read' => false,
            ],
            [
                'user_id' => $user1 ? $user1->id : $admin->id,
                'title'   => 'Transaksi Baru Menunggu Persetujuan',
                'message' => 'Terdapat 2 transaksi baru yang memerlukan persetujuan bendahara.',
                'type'    => 'warning',
                'icon'    => 'fa-clock',
                'is_read' => false,
            ],
            [
                'user_id' => $user2 ? $user2->id : $admin->id,
                'title'   => 'Anda Telah Ditambahkan ke Organisasi',
                'message' => 'Anda telah bergabung sebagai Sekretaris di MoneFlo Tech Community.',
                'type'    => 'info',
                'icon'    => 'fa-users',
                'is_read' => true,
                'read_at' => now()->subHours(2),
            ],
            [
                'user_id' => $user3 ? $user3->id : $admin->id,
                'title'   => 'Agenda Rapat Mendatang',
                'message' => 'Jangan lupa! Rapat Evaluasi Bulanan akan dilaksanakan 3 hari lagi.',
                'type'    => 'info',
                'icon'    => 'fa-calendar',
                'is_read' => false,
            ],
            [
                'user_id' => $admin->id,
                'title'   => 'Laporan Keuangan Tersedia',
                'message' => 'Laporan keuangan bulan lalu telah siap untuk diunduh.',
                'type'    => 'success',
                'icon'    => 'fa-file-alt',
                'is_read' => false,
            ],
        ];

        foreach ($notifications as $data) {
            Notification::create($data);
        }

        $this->command->info('✅ NotificationSeeder: 5 notifikasi berhasil dibuat');
    }
}
