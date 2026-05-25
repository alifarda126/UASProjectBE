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
            ['email' => 'moneflosup@gmail.com'],
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

    }
}
