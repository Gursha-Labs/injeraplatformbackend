<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VariableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('variables')->insert([
            [
                'id' => Str::uuid(),
                'point' => "earn_point",
                "type" => "earn_point",
                "value" => 5,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()

            ],
            [
                'id' => Str::uuid(),
                'point' => "bet_point",
                "value" => 5,
                "type" => "bet_point",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()

            ]
        ]);
    }
}
