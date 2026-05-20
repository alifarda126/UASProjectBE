<?php

namespace Database\Seeders;

use App\Models\Organisasi;
use App\Models\AnggotaOrganisasi;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder: Organisasi contoh beserta keanggotaannya.
 */
class OrganisasiSeeder extends Seeder
{
    public function run(): void
    {
        $admin  = User::where('email', 'moneflosupport@gmail.com')->first();
        $user1  = User::where('email', 'budi.santoso@example.com')->first();
        $user2  = User::where('email', 'siti.rahayu@example.com')->first();
        $user3  = User::where('email', 'ahmad.fauzi@example.com')->first();

        if (!$admin) {
            $this->command->warn('AdminSeeder belum dijalankan. Skip OrganisasiSeeder.');
            return;
        }

        // ── Organisasi Contoh ──
        $org = Organisasi::updateOrCreate(
            ['code' => 'MFTC2024'],
            [
                'name'        => 'MoneFlo Tech Community',
                'code'        => 'MFTC2024',
                'description' => 'Komunitas teknologi MoneFlo yang berfokus pada inovasi dan kolaborasi antar mahasiswa teknologi.',
                'type'        => 'Kemahasiswaan',
                'email'       => 'community@moneflo.com',
                'phone'       => '0812-3456-7890',
                'address'     => 'Gedung Teknologi Lt. 3, Kampus Utama',
                'is_active'   => true,
                'created_by'  => $admin->id,
            ]
        );

        // ── Keanggotaan Organisasi ──
        $memberships = [
            [$admin->id,  'ketua'],
            [$user1->id,  'bendahara'],
            [$user2->id,  'sekretaris'],
            [$user3->id,  'anggota'],
        ];

        foreach ($memberships as [$userId, $role]) {
            if ($userId) {
                AnggotaOrganisasi::updateOrCreate(
                    ['user_id' => $userId, 'organisasi_id' => $org->id],
                    [
                        'role'      => $role,
                        'joined_at' => now()->subMonths(3),
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('✅ OrganisasiSeeder: 1 organisasi + 4 anggota berhasil dibuat');
    }
}
