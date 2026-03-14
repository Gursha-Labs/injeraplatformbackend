<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RewardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('rewards')->insert([
            [
                'id' => Str::uuid(),
                'name' => '$5 Gift Card',
                'type' => 'money',
                'value' => 5.00,
                'probability' => 30,
                'description' => 'Win a $5 gift card',
                'icon' => 'gift-card.png',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => Str::uuid(),
                'name' => '$20 Cash',
                'type' => 'money',
                'value' => 20.00,
                'probability' => 15,
                'description' => 'Win $20 cash prize',
                'icon' => 'cash.png',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => Str::uuid(),
                'name' => '50 Loyalty Points',
                'type' => 'point',
                'value' => 50,
                'probability' => 25,
                'description' => 'Earn 50 loyalty points',
                'icon' => 'points.png',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => Str::uuid(),
                'name' => '3-Day Premium Trial',
                'type' => 'trial',
                'value' => 3,
                'probability' => 10,
                'description' => '3 days free premium access',
                'icon' => 'premium.png',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Better Luck Next Time',
                'type' => 'lose',
                'value' => 0,
                'probability' => 20,
                'description' => 'No prize this time',
                'icon' => 'sorry.png',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
        ]);
    }
}
