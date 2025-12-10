<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'id'       => (string) Str::uuid(),
            'username' => 'admin',
            'email'    => 'admin@injera.et',
            'password' => bcrypt('admin123'),
            'type'     => 'admin',
            'email_verified_at' => now(),
        ]);
    }
}