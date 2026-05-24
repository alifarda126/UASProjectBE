<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Organisasi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeder: 5 agenda mendatang untuk organisasi contoh.
 */
class AgendaSeeder extends Seeder
{
    public function run(): void
    {
        $org   = Organisasi::where('code', 'MFTC2024')->first();
        $admin = User::where('email', 'moneflosupport@gmail.com')->first();

        if (!$org || !$admin) {
            $this->command->warn('Prerequisite seeder belum dijalankan. Skip AgendaSeeder.');
            return;
        }

        $agendas = [
            [
                'title'       => 'Rapat Evaluasi Bulanan',
                'description' => 'Evaluasi kegiatan dan keuangan bulan lalu serta perencanaan bulan depan.',
                'location'    => 'Ruang Rapat Gedung Teknologi Lt. 3',
                'start_at'    => Carbon::now()->addDays(3)->setTime(14, 0),
                'end_at'      => Carbon::now()->addDays(3)->setTime(16, 0),
                'type'        => 'rapat',
                'status'      => 'upcoming',
            ],
            [
                'title'       => 'Workshop Laravel & REST API',
                'description' => 'Workshop intensif membangun REST API menggunakan Laravel Sanctum dan Socialite.',
                'location'    => 'Lab Komputer Gedung B, Lantai 2',
                'start_at'    => Carbon::now()->addWeeks(1)->setTime(9, 0),
                'end_at'      => Carbon::now()->addWeeks(1)->setTime(17, 0),
                'type'        => 'workshop',
                'status'      => 'upcoming',
            ],
            [
                'title'       => 'Gathering Semester Genap',
                'description' => 'Acara gathering seluruh anggota komunitas untuk mempererat kebersamaan.',
                'location'    => 'Taman Kampus Area Timur',
                'start_at'    => Carbon::now()->addWeeks(2)->setTime(10, 0),
                'end_at'      => Carbon::now()->addWeeks(2)->setTime(15, 0),
                'type'        => 'gathering',
                'status'      => 'upcoming',
            ],
            [
                'title'       => 'Seminar Teknologi AI & Machine Learning',
                'description' => 'Seminar menghadirkan pembicara dari industri tentang perkembangan AI terkini.',
                'location'    => 'Aula Besar Kampus, Gedung Rektorat',
                'start_at'    => Carbon::now()->addWeeks(3)->setTime(8, 30),
                'end_at'      => Carbon::now()->addWeeks(3)->setTime(12, 0),
                'type'        => 'seminar',
                'status'      => 'upcoming',
            ],
            [
                'title'       => 'Rapat Persiapan Kompetisi Hackathon',
                'description' => 'Rapat pembentukan tim dan persiapan untuk mengikuti hackathon nasional.',
                'location'    => 'Online via Google Meet',
                'start_at'    => Carbon::now()->addMonths(1)->setTime(19, 0),
                'end_at'      => Carbon::now()->addMonths(1)->setTime(21, 0),
                'type'        => 'rapat',
                'status'      => 'upcoming',
            ],
        ];

        foreach ($agendas as $data) {
            Agenda::updateOrCreate(
                ['organisasi_id' => $org->id, 'title' => $data['title']],
                array_merge($data, [
                    'organisasi_id' => $org->id,
                    'user_id'       => $admin->id,
                ])
            );
        }

        $this->command->info('✅ AgendaSeeder: 5 agenda berhasil dibuat');
    }
}
