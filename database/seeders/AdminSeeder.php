<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder: Data admin default dan user contoh untuk database moneflo.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin Default ──
        User::updateOrCreate(
            ['email' => 'moneflosupp@gmail.com'],
            [
                'name'              => 'Super Admin',
                'email'             => 'moneflosupp@gmail.com',
                'password'          => Hash::make('Admin123'),
                'role'              => 'admin',
                'is_active'         => true,
                'email_verified_at' => now(),
                'avatar'            => null,
            ]
        );

        // ── User Contoh 1 ──
        User::updateOrCreate(
            ['email' => 'budi.santoso@example.com'],
            [
                'name'              => 'Budi Santoso',
                'email'             => 'budi.santoso@example.com',
                'password'          => Hash::make('password'),
                'role'              => 'user',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        // ── User Contoh 2 ──
        User::updateOrCreate(
            ['email' => 'siti.rahayu@example.com'],
            [
                'name'              => 'Siti Rahayu',
                'email'             => 'siti.rahayu@example.com',
                'password'          => Hash::make('password'),
                'role'              => 'user',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        // ── User Contoh 3 ──
        User::updateOrCreate(
            ['email' => 'ahmad.fauzi@example.com'],
            [
                'name'              => 'Ahmad Fauzi',
                'email'             => 'ahmad.fauzi@example.com',
                'password'          => Hash::make('password'),
                'role'              => 'user',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ AdminSeeder: 1 admin + 3 user berhasil dibuat');
    }
}
