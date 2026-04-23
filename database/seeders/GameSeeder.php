<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;
use Illuminate\Support\Str;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        // Spin Wheel Game
        Game::create([
            'id' => Str::uuid(),
            'name' => 'Lucky Spin Wheel',
            'type' => 'spin_wheel',
            'wheel_labels' => ['$30', '$10', '$250', '$20', 'LOSE', '$5', '$500', '$80'],
            'wheel_colors' => ['yellow', 'orange', 'red', 'pink', 'grey', 'teal', 'blue', 'purple'],
            'wheel_prizes' => [30, 10, 250, 20, 0, 5, 500, 80],
            'min_bet' => 1.00,
            'max_bet' => 100.00,
            'is_active' => true,
            'description' => 'Spin the wheel and win amazing prizes!'
        ]);

        // Slot Machine Game
        Game::create([
            'id' => Str::uuid(),
            'name' => 'Classic Slot Machine',
            'type' => 'slot_machine',
            'slot_symbols' => ['🍒', '🍋', '7️⃣', '💎', '⭐', '🔔'],
            'slot_payouts' => [
                ['symbol' => '7️⃣', 'count' => 3, 'multiplier' => 10],
                ['symbol' => '💎', 'count' => 3, 'multiplier' => 8],
                ['symbol' => '⭐', 'count' => 3, 'multiplier' => 5],
                ['symbol' => '🔔', 'count' => 3, 'multiplier' => 3],
            ],
            'slot_reel_count' => 3,
            'min_bet' => 0.50,
            'max_bet' => 50.00,
            'is_active' => true,
            'description' => 'Classic 3-reel slot machine with fruit symbols!'
        ]);
    }
}
