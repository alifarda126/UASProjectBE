<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * OrganisasiSeeder — Data organisasi + keanggotaan dari backup MoneFlo.
 */
class OrganisasiSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('anggota_organisasi')->truncate();
        DB::table('organisasi')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── Organisasi ──
        DB::table('organisasi')->insert([
            [
                'id'               => 1,
                'name'             => 'Futsal Club',
                'code'             => 'FUTS3843',
                'description'      => null,
                'type'             => 'Unit Kegiatan Mahasiswa',
                'logo'             => 'logos/cHAfZTG6olR1RwHp4vpa53gIcls0MkqMK21EVsFJ.png',
                'email'            => 'kosmail012@gmail.com',
                'phone'            => '11111111111',
                'address'          => null,
                'is_active'        => 1,
                'dues_interval'    => 7,
                'dues_amount'      => 15000.00,
                'is_suspended'     => 0,
                'suspended_reason' => null,
                'suspended_at'     => null,
                'created_by'       => 2,
                'created_at'       => '2026-05-26 06:08:25',
                'updated_at'       => '2026-05-26 17:34:52',
                'deleted_at'       => null,
            ],
            [
                'id'               => 2,
                'name'             => 'Oskab Sayur',
                'code'             => 'OSKA2046',
                'description'      => null,
                'type'             => 'Kemahasiswaan',
                'logo'             => null,
                'email'            => 'baksosayur1997@gmail.com',
                'phone'            => '085977573780',
                'address'          => null,
                'is_active'        => 1,
                'dues_interval'    => 30,
                'dues_amount'      => 15000.00,
                'is_suspended'     => 0,
                'suspended_reason' => null,
                'suspended_at'     => null,
                'created_by'       => 4,
                'created_at'       => '2026-05-26 13:50:32',
                'updated_at'       => '2026-05-28 03:02:42',
                'deleted_at'       => null,
            ],
            [
                'id'               => 3,
                'name'             => 'valo',
                'code'             => 'VALO7531',
                'description'      => null,
                'type'             => 'Komunitas',
                'logo'             => null,
                'email'            => 'nolantino29@gmail.com',
                'phone'            => '08123456789',
                'address'          => null,
                'is_active'        => 1,
                'dues_interval'    => 7,
                'dues_amount'      => 15000.00,
                'is_suspended'     => 0,
                'suspended_reason' => null,
                'suspended_at'     => null,
                'created_by'       => 5,
                'created_at'       => '2026-05-26 14:02:52',
                'updated_at'       => '2026-05-26 14:02:52',
                'deleted_at'       => null,
            ],
            [
                'id'               => 4,
                'name'             => 'Aditya',
                'code'             => 'ADIT3169',
                'description'      => 'Adit',
                'type'             => 'Himpunan Mahasiswa',
                'logo'             => null,
                'email'            => 'aditkaryudie@gmail.com',
                'phone'            => '082190220487',
                'address'          => null,
                'is_active'        => 1,
                'dues_interval'    => 7,
                'dues_amount'      => 9999999999.00,
                'is_suspended'     => 0,
                'suspended_reason' => null,
                'suspended_at'     => null,
                'created_by'       => 6,
                'created_at'       => '2026-05-27 05:37:20',
                'updated_at'       => '2026-05-28 03:01:24',
                'deleted_at'       => null,
            ],
            [
                'id'               => 5,
                'name'             => 'UKM ESPORT UNAIR',
                'code'             => 'UKME1842',
                'description'      => 'game',
                'type'             => 'Unit Kegiatan Mahasiswa',
                'logo'             => null,
                'email'            => 'altaf.hermansyah2007@gmail.com',
                'phone'            => '08176338327',
                'address'          => null,
                'is_active'        => 1,
                'dues_interval'    => 7,
                'dues_amount'      => 15000.00,
                'is_suspended'     => 0,
                'suspended_reason' => null,
                'suspended_at'     => null,
                'created_by'       => 7,
                'created_at'       => '2026-05-28 02:59:19',
                'updated_at'       => '2026-05-28 02:59:19',
                'deleted_at'       => null,
            ],
        ]);

        // ── Anggota Organisasi ──
        DB::table('anggota_organisasi')->insert([
            [
                'id'             => 1,
                'user_id'        => 2,
                'organisasi_id'  => 1,
                'role'           => 'ketua',
                'joined_at'      => '2026-05-26',
                'is_active'      => 1,
                'created_at'     => '2026-05-26 06:08:25',
                'updated_at'     => '2026-05-26 06:08:25',
            ],
            [
                'id'             => 2,
                'user_id'        => 4,
                'organisasi_id'  => 2,
                'role'           => 'ketua',
                'joined_at'      => '2026-05-26',
                'is_active'      => 1,
                'created_at'     => '2026-05-26 13:50:32',
                'updated_at'     => '2026-05-26 13:50:32',
            ],
            [
                'id'             => 3,
                'user_id'        => 5,
                'organisasi_id'  => 3,
                'role'           => 'ketua',
                'joined_at'      => '2026-05-26',
                'is_active'      => 1,
                'created_at'     => '2026-05-26 14:02:52',
                'updated_at'     => '2026-05-26 14:02:52',
            ],
            [
                'id'             => 4,
                'user_id'        => 6,
                'organisasi_id'  => 4,
                'role'           => 'ketua',
                'joined_at'      => '2026-05-27',
                'is_active'      => 1,
                'created_at'     => '2026-05-27 05:37:20',
                'updated_at'     => '2026-05-27 05:37:20',
            ],
            [
                'id'             => 5,
                'user_id'        => 7,
                'organisasi_id'  => 5,
                'role'           => 'ketua',
                'joined_at'      => '2026-05-28',
                'is_active'      => 1,
                'created_at'     => '2026-05-28 02:59:19',
                'updated_at'     => '2026-05-28 02:59:19',
            ],
        ]);

        $this->command->info('✅ OrganisasiSeeder: 5 organisasi + 5 anggota berhasil di-seed.');
    }
}
