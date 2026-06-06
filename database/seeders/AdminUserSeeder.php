<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // NOTE: The User model has a password mutator that hashes on assignment,
        // so pass the PLAIN password here — do NOT Hash::make() (would double-hash).
        User::updateOrCreate(
            ['email' => 'admin@lensmania.ae'],
            [
                'name' => 'Lensmania Admin',
                'password' => 'LensmaniaAdmin2026!',
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
