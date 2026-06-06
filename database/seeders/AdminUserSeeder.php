<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@lensmania.ae'],
            [
                'name' => 'Lensmania Admin',
                'password' => Hash::make('LensmaniaAdmin2026!'),
                'is_admin' => true,
            ]
        );
    }
}
