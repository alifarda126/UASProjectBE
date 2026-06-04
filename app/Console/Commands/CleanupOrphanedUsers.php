<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOrphanedUsers extends Command
{
    protected $signature = 'cleanup:orphaned-users';
    protected $description = 'Hapus user yang organisasinya sudah dihapus (orphaned users)';

    public function handle()
    {
        // Cari user yang BUKAN admin dan TIDAK punya keanggotaan organisasi
        $orphanedUsers = DB::table('users')
            ->where('role', '!=', 'admin')
            ->whereNotIn('id', function ($q) {
                $q->select('user_id')->from('anggota_organisasi');
            })
            ->get();

        if ($orphanedUsers->isEmpty()) {
            $this->info('Tidak ada orphaned user yang perlu dihapus.');
            return 0;
        }

        $this->info("Ditemukan {$orphanedUsers->count()} orphaned user:");
        foreach ($orphanedUsers as $u) {
            $this->line("  - [{$u->id}] {$u->name} ({$u->email})");
        }

        $ids = $orphanedUsers->pluck('id')->toArray();

        DB::table('personal_access_tokens')->whereIn('tokenable_id', $ids)->delete();
        DB::table('user_sessions')->whereIn('user_id', $ids)->delete();
        DB::table('notifications')->whereIn('user_id', $ids)->delete();
        DB::table('anggota_organisasi')->whereIn('user_id', $ids)->delete();
        DB::table('users')->whereIn('id', $ids)->delete();

        $this->info("✅ {$orphanedUsers->count()} orphaned user berhasil dihapus.");
        return 0;
    }
}
