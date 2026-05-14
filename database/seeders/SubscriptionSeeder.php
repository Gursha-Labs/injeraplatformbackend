<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subscription;
use Illuminate\Support\Str;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subscriptions = [
            [
                'name' => 'Weekly Plan',
                'slug' => 'weekly',
                'description' => 'Perfect for testing. Upload up to 5 videos per week.',
                'price' => 99.99,
                'currency' => 'ETB',
                'duration_days' => 7,
                'video_upload_limit' => 5,
                'max_video_duration_seconds' => 300, // 5 minutes
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Monthly Plan',
                'slug' => 'monthly',
                'description' => 'Our most popular plan. Upload up to 30 videos per month with extended duration.',
                'price' => 399.99,
                'currency' => 'ETB',
                'duration_days' => 30,
                'video_upload_limit' => 30,
                'max_video_duration_seconds' => 600, // 10 minutes
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Annual Plan',
                'slug' => 'annual',
                'description' => 'Best value! Upload up to 500 videos per year with unlimited video duration.',
                'price' => 3999.99,
                'currency' => 'ETB',
                'duration_days' => 365,
                'video_upload_limit' => 500,
                'max_video_duration_seconds' => 1800, // 30 minutes
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($subscriptions as $subscription) {
            Subscription::updateOrCreate(
                ['slug' => $subscription['slug']],
                array_merge($subscription, ['id' => (string) Str::uuid()])
            );
        }
    }
}
