<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@stayops.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'locale' => 'ro',
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('SuperAdmin');
    }
}
