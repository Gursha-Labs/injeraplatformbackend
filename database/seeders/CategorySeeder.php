<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Food & Beverage',
            'Technology',
            'Fashion',
            'Health & Wellness',
            'Education',
            'Entertainment',
            'Travel',
            'Automotive',
            'Finance',
            'Real Estate'
        ];

        foreach ($categories as $name) {
            Category::create([
                'id' => (string) Str::uuid(),
                'name' => $name
            ]);
        }
    }
}