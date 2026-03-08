<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'wheel_labels',
        'wheel_colors',
        'wheel_prizes',
        'slot_symbols',
        'slot_payouts',
        'slot_reel_count',
        'bet_amount',
        'max_bet',
        'min_bet',
        'max_spins_per_day',
        'is_active',
        'settings',
        'description'
    ];

    protected $casts = [
        'wheel_labels' => 'array',
        'wheel_colors' => 'array',
        'wheel_prizes' => 'array',
        'slot_symbols' => 'array',
        'slot_payouts' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'bet_amount' => 'decimal:2',
        'max_bet' => 'decimal:2',
        'min_bet' => 'decimal:2'
    ];

    // Helper method to get spin wheel configuration
    public function getSpinWheelConfig()
    {
        return [
            'labels' => $this->wheel_labels ?? ['$30', '$10', '$250', '$20', 'LOSE', '$5', '$500', '$80'],
            'colors' => $this->wheel_colors ?? ['yellow', 'orange', 'red', 'pink', 'grey', 'teal', 'blue', 'purple'],
            'prizes' => $this->wheel_prizes ?? []
        ];
    }

    // Helper method to get slot machine configuration
    public function getSlotMachineConfig()
    {
        return [
            'symbols' => $this->slot_symbols ?? ['🍒', '🍋', '7️⃣', '💎', '⭐', '🔔'],
            'payouts' => $this->slot_payouts ?? [],
            'reel_count' => $this->slot_reel_count ?? 3
        ];
    }
}