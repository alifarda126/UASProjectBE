<?php

namespace Database\Seeders;

use App\Models\Organisasi;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeder: 10 transaksi contoh untuk 3 bulan terakhir.
 */
class TransaksiSeeder extends Seeder
{
    public function run(): void
    {
        $org   = Organisasi::where('code', 'MFTC2024')->first();
        $admin = User::where('email', 'moneflosupport@gmail.com')->first();
        $user1 = User::where('email', 'budi.santoso@example.com')->first();
        $user2 = User::where('email', 'siti.rahayu@example.com')->first();

        if (!$org || !$admin) {
            $this->command->warn('Prerequisite seeder belum dijalankan. Skip TransaksiSeeder.');
            return;
        }

        $now = Carbon::now();

        $transaksi = [
            // Bulan -3
            [
                'type' => 'pemasukan', 'category' => 'Iuran',
                'description' => 'Iuran bulanan anggota Februari', 'amount' => 2500000,
                'date' => $now->copy()->subMonths(3)->startOfMonth()->addDays(5),
                'status' => 'approved', 'user_id' => $user1->id, 'approved_by' => $admin->id,
            ],
            [
                'type' => 'pengeluaran', 'category' => 'Operasional',
                'description' => 'Sewa ruang rapat bulanan', 'amount' => 500000,
                'date' => $now->copy()->subMonths(3)->startOfMonth()->addDays(10),
                'status' => 'approved', 'user_id' => $user2->id, 'approved_by' => $admin->id,
            ],
            // Bulan -2
            [
                'type' => 'pemasukan', 'category' => 'Sponsor',
                'description' => 'Sponsor dari PT Teknologi Nusantara', 'amount' => 5000000,
                'date' => $now->copy()->subMonths(2)->startOfMonth()->addDays(3),
                'status' => 'approved', 'user_id' => $admin->id, 'approved_by' => $admin->id,
            ],
            [
                'type' => 'pengeluaran', 'category' => 'Konsumsi',
                'description' => 'Konsumsi workshop React.js', 'amount' => 750000,
                'date' => $now->copy()->subMonths(2)->startOfMonth()->addDays(15),
                'status' => 'approved', 'user_id' => $user1->id, 'approved_by' => $admin->id,
            ],
            [
                'type' => 'pemasukan', 'category' => 'Iuran',
                'description' => 'Iuran bulanan anggota Maret', 'amount' => 2500000,
                'date' => $now->copy()->subMonths(2)->startOfMonth()->addDays(7),
                'status' => 'approved', 'user_id' => $user1->id, 'approved_by' => $admin->id,
            ],
            [
                'type' => 'pengeluaran', 'category' => 'Perlengkapan',
                'description' => 'Pembelian whiteboard marker & spidol', 'amount' => 150000,
                'date' => $now->copy()->subMonths(2)->startOfMonth()->addDays(20),
                'status' => 'rejected', 'user_id' => $user2->id, 'approved_by' => $admin->id,
            ],
            // Bulan -1
            [
                'type' => 'pemasukan', 'category' => 'Donasi',
                'description' => 'Donasi alumni batch 2021', 'amount' => 1500000,
                'date' => $now->copy()->subMonths(1)->startOfMonth()->addDays(2),
                'status' => 'approved', 'user_id' => $admin->id, 'approved_by' => $admin->id,
            ],
            [
                'type' => 'pengeluaran', 'category' => 'Acara',
                'description' => 'Biaya sound system gathering tahunan', 'amount' => 2000000,
                'date' => $now->copy()->subMonths(1)->startOfMonth()->addDays(12),
                'status' => 'approved', 'user_id' => $user1->id, 'approved_by' => $admin->id,
            ],
            // Bulan ini
            [
                'type' => 'pemasukan', 'category' => 'Iuran',
                'description' => 'Iuran bulanan anggota bulan ini', 'amount' => 2500000,
                'date' => $now->copy()->startOfMonth()->addDays(5),
                'status' => 'pending', 'user_id' => $user1->id, 'approved_by' => null,
            ],
            [
                'type' => 'pengeluaran', 'category' => 'Operasional',
                'description' => 'Perpanjangan domain & hosting website', 'amount' => 350000,
                'date' => $now->copy()->startOfMonth()->addDays(8),
                'status' => 'pending', 'user_id' => $user2->id, 'approved_by' => null,
            ],
        ];

        foreach ($transaksi as $data) {
            Transaksi::updateOrCreate(
                [
                    'organisasi_id' => $org->id,
                    'description'   => $data['description'],
                ],
                array_merge($data, [
                    'organisasi_id' => $org->id,
                    'approved_at'   => $data['status'] !== 'pending' ? $data['date']->copy()->addDays(2) : null,
                ])
            );
        }

        $this->command->info('✅ TransaksiSeeder: 10 transaksi berhasil dibuat');
    }
}
