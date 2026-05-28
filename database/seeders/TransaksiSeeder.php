<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * TransaksiSeeder — Data transaksi keuangan dari backup MoneFlo.
 * Catatan: kolom 'docs' berisi JSON base64 gambar yang sangat besar,
 * di sini di-set null untuk menghindari ukuran seed yang bloat.
 */
class TransaksiSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('transaksi')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::table('transaksi')->insert([
            [
                'id'            => 1,
                'organisasi_id' => 1,
                'user_id'       => 2,
                'approved_by'   => 2,
                'type'          => 'pemasukan',
                'category'      => 'Operasional',
                'description'   => 'Test',
                'amount'        => 100000.00,
                'date'          => '2026-05-26',
                'status'        => 'approved',
                'notes'         => null,
                'attachment'    => null,
                'docs'          => null, // file base64 terlalu besar untuk seeder
                'approved_at'   => '2026-05-26 06:08:34',
                'created_at'    => '2026-05-26 06:08:34',
                'updated_at'    => '2026-05-26 06:08:34',
                'deleted_at'    => null,
            ],
            [
                'id'            => 2,
                'organisasi_id' => 1,
                'user_id'       => 2,
                'approved_by'   => 2,
                'type'          => 'pengeluaran',
                'category'      => 'Operasional',
                'description'   => 'Test Pengeluaran',
                'amount'        => 50000.00,
                'date'          => '2026-05-26',
                'status'        => 'approved',
                'notes'         => null,
                'attachment'    => null,
                'docs'          => null,
                'approved_at'   => '2026-05-26 06:09:00',
                'created_at'    => '2026-05-26 06:09:00',
                'updated_at'    => '2026-05-26 06:09:00',
                'deleted_at'    => null,
            ],
            [
                'id'            => 3,
                'organisasi_id' => 2,
                'user_id'       => 4,
                'approved_by'   => null,
                'type'          => 'pemasukan',
                'category'      => 'Kas',
                'description'   => 'Iuran Bulanan',
                'amount'        => 75000.00,
                'date'          => '2026-05-26',
                'status'        => 'pending',
                'notes'         => null,
                'attachment'    => null,
                'docs'          => null,
                'approved_at'   => null,
                'created_at'    => '2026-05-26 13:55:00',
                'updated_at'    => '2026-05-26 13:55:00',
                'deleted_at'    => null,
            ],
            [
                'id'            => 4,
                'organisasi_id' => 3,
                'user_id'       => 5,
                'approved_by'   => null,
                'type'          => 'pemasukan',
                'category'      => 'Donasi',
                'description'   => 'Donasi Anggota',
                'amount'        => 200000.00,
                'date'          => '2026-05-26',
                'status'        => 'pending',
                'notes'         => null,
                'attachment'    => null,
                'docs'          => null,
                'approved_at'   => null,
                'created_at'    => '2026-05-26 14:10:00',
                'updated_at'    => '2026-05-26 14:10:00',
                'deleted_at'    => null,
            ],
            [
                'id'            => 5,
                'organisasi_id' => 4,
                'user_id'       => 6,
                'approved_by'   => null,
                'type'          => 'pengeluaran',
                'category'      => 'Perlengkapan',
                'description'   => 'Beli Alat',
                'amount'        => 500000.00,
                'date'          => '2026-05-27',
                'status'        => 'pending',
                'notes'         => null,
                'attachment'    => null,
                'docs'          => null,
                'approved_at'   => null,
                'created_at'    => '2026-05-27 05:45:00',
                'updated_at'    => '2026-05-27 05:45:00',
                'deleted_at'    => null,
            ],
            [
                'id'            => 6,
                'organisasi_id' => 5,
                'user_id'       => 7,
                'approved_by'   => null,
                'type'          => 'pemasukan',
                'category'      => 'Kas',
                'description'   => 'Iuran Awal',
                'amount'        => 15000.00,
                'date'          => '2026-05-28',
                'status'        => 'pending',
                'notes'         => null,
                'attachment'    => null,
                'docs'          => null,
                'approved_at'   => null,
                'created_at'    => '2026-05-28 03:05:00',
                'updated_at'    => '2026-05-28 03:05:00',
                'deleted_at'    => null,
            ],
            [
                'id'            => 7,
                'organisasi_id' => 1,
                'user_id'       => 2,
                'approved_by'   => 2,
                'type'          => 'pemasukan',
                'category'      => 'Kas',
                'description'   => 'Kas Mingguan',
                'amount'        => 45000.00,
                'date'          => '2026-05-28',
                'status'        => 'approved',
                'notes'         => null,
                'attachment'    => null,
                'docs'          => null,
                'approved_at'   => '2026-05-28 07:00:00',
                'created_at'    => '2026-05-28 07:00:00',
                'updated_at'    => '2026-05-28 07:00:00',
                'deleted_at'    => null,
            ],
        ]);

        $this->command->info('✅ TransaksiSeeder: 7 transaksi berhasil di-seed.');
    }
}
