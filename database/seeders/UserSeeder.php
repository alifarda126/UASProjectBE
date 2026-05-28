<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * UserSeeder — Memuat data user dari backup database MoneFlo.
 * Catatan: password sudah di-hash (bcrypt), tidak perlu di-hash ulang.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Nonaktifkan foreign key check sementara
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Truncate agar tidak ada duplikasi saat re-seed
        DB::table('users')->truncate();

        DB::table('users')->insert([
            [
                'id'                => 1,
                'name'              => 'Super Admin',
                'email'             => 'moneflosupp@gmail.com',
                'avatar'            => null,
                'provider'          => null,
                'provider_id'       => null,
                'provider_token'    => null,
                'role'              => 'admin',
                'is_active'         => 1,
                'last_login_at'     => '2026-05-28 06:30:22',
                'email_verified_at' => '2026-05-26 05:48:44',
                'password'          => '$2y$12$BlNtDkxolIimNQ5y0w7bc.hWtG4zOFv1IUb3DD4rgTPgtwNCZTaZq',
                'remember_token'    => null,
                'created_at'        => '2026-05-26 05:48:44',
                'updated_at'        => '2026-05-28 06:30:22',
            ],
            [
                'id'                => 2,
                'name'              => 'Admin Futsal Club',
                'email'             => 'kosmail012@gmail.com',
                'avatar'            => null,
                'provider'          => null,
                'provider_id'       => null,
                'provider_token'    => null,
                'role'              => 'user',
                'is_active'         => 1,
                'last_login_at'     => '2026-05-27 07:58:27',
                'email_verified_at' => '2026-05-26 06:08:25',
                'password'          => '$2y$12$m/w66iYm0vS1WEIdMmZikebg6vjQ3IXde2gw4GUO8l3PS0o5PqMPu',
                'remember_token'    => null,
                'created_at'        => '2026-05-26 06:08:25',
                'updated_at'        => '2026-05-27 07:58:27',
            ],
            [
                'id'                => 4,
                'name'              => 'Admin Oskab Sayur',
                'email'             => 'baksosayur1997@gmail.com',
                'avatar'            => 'https://lh3.googleusercontent.com/a/ACg8ocIY9Gdm0J9WtGj5iSsstZyPWcv4LRj2Gdhg2_O_2i0Y4UEZoQ=s96-c',
                'provider'          => 'google',
                'provider_id'       => '114380943454174641415',
                'provider_token'    => null, // token OAuth dihapus karena expired
                'role'              => 'user',
                'is_active'         => 1,
                'last_login_at'     => '2026-05-28 06:21:49',
                'email_verified_at' => '2026-05-26 13:50:32',
                'password'          => '$2y$12$oBNPhzhRNhvrZnELBb4JteGZqeZI1e2gFJ/4Mj3slmh0VZw15xT9O',
                'remember_token'    => null,
                'created_at'        => '2026-05-26 13:50:32',
                'updated_at'        => '2026-05-28 06:21:49',
            ],
            [
                'id'                => 5,
                'name'              => 'Admin valo',
                'email'             => 'nolantino29@gmail.com',
                'avatar'            => null,
                'provider'          => null,
                'provider_id'       => null,
                'provider_token'    => null,
                'role'              => 'user',
                'is_active'         => 1,
                'last_login_at'     => '2026-05-26 14:03:42',
                'email_verified_at' => '2026-05-26 14:02:52',
                'password'          => '$2y$12$x84cX7wTtcpaVtsnugsJ5uzaw/Jq2b4dPS0WJ5WR3v5.aVPkIF1dG',
                'remember_token'    => null,
                'created_at'        => '2026-05-26 14:02:52',
                'updated_at'        => '2026-05-26 14:03:42',
            ],
            [
                'id'                => 6,
                'name'              => 'Admin Aditya',
                'email'             => 'aditkaryudie@gmail.com',
                'avatar'            => 'https://lh3.googleusercontent.com/a/ACg8ocIAzCOQLXeIHKft2sNVvFmwz_YaS9Km1KUc9ETkS2heMDRDPgFZ=s96-c',
                'provider'          => 'google',
                'provider_id'       => '117542459585763900953',
                'provider_token'    => null, // token OAuth dihapus karena expired
                'role'              => 'user',
                'is_active'         => 1,
                'last_login_at'     => '2026-05-28 02:59:11',
                'email_verified_at' => '2026-05-27 05:37:20',
                'password'          => '$2y$12$J2u0NvPX/l7p5GCZjrH6lOLXRK6iAvPExDVoFi4y.8uJWE6X5kh/C',
                'remember_token'    => null,
                'created_at'        => '2026-05-27 05:37:20',
                'updated_at'        => '2026-05-28 02:59:11',
            ],
            [
                'id'                => 7,
                'name'              => 'Admin UKM ESPORT UNAIR',
                'email'             => 'altaf.hermansyah2007@gmail.com',
                'avatar'            => null,
                'provider'          => 'google',
                'provider_id'       => null,
                'provider_token'    => null,
                'role'              => 'user',
                'is_active'         => 1,
                'last_login_at'     => '2026-05-28 02:59:30',
                'email_verified_at' => '2026-05-28 02:59:19',
                'password'          => '$2y$12$LMwZp90weN1WpJtY5BK/IO/uypUfNtvAkbT.3XJ2TbF.XlZwi5xyq',
                'remember_token'    => null,
                'created_at'        => '2026-05-28 02:59:19',
                'updated_at'        => '2026-05-28 02:59:30',
            ],
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('✅ UserSeeder: 6 user berhasil di-seed.');
    }
}
