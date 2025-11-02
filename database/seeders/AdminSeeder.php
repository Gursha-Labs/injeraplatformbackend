<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            'username' => 'admin',
            'email'    => 'admin@injera.et',
            'password' => bcrypt('admin123'),
            'type'     => 'admin',
        ]);
    }
}
