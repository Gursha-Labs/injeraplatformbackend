<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Game_Player extends Model
{
    use HasFactory;
    use HasUuid;
    protected $table = 'game_players';

    protected $fillable = [
        'user_id',
        'game_id',
        'is_allowed',
        'is_active',
        'is_banned',
        'win_probability',
        'win_frequency',
        'spin_count',
        'win_count',
        'consecutive_losses',
        'consecutive_wins',
        'total_bet_amount',
        'total_win_amount',
        'net_profit',
        'last_spin_at',
        'last_win_at',
        'banned_until',
        'player_settings'
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
        'is_active' => 'boolean',
        'is_banned' => 'boolean',
        'win_probability' => 'decimal:4',
        'win_frequency' => 'integer',
        'spin_count' => 'integer',
        'win_count' => 'integer',
        'consecutive_losses' => 'integer',
        'consecutive_wins' => 'integer',
        'total_bet_amount' => 'decimal:2',
        'total_win_amount' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'last_spin_at' => 'datetime',
        'last_win_at' => 'datetime',
        'banned_until' => 'datetime',
        'player_settings' => 'array'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    // Check if player can win
    public function canWin(): bool
    {
        // Check if player is allowed to win
        if (!$this->is_allowed) {
            Log::info("Player {$this->user_id} is not allowed to win");
            return false;
        }

        // Check if player is banned
        if ($this->is_banned) {
            if ($this->banned_until && now()->lt($this->banned_until)) {
                Log::info("Player {$this->user_id} is banned until {$this->banned_until}");
                return false;
            }

            // If ban period is over, unban the player
            if ($this->banned_until && now()->gte($this->banned_until)) {
                $this->update(['is_banned' => false, 'banned_until' => null]);
            }
        }

        return true;
    }

    // Determine if this spin is a winner (with scarce probability)
    public function determineWin(): bool
    {
        if (!$this->canWin()) {
            return false;
        }

        // Use win_frequency for probability (1 in X chance)
        // Default 1000 means 1 in 1000 chance (0.1%)
        $frequency = $this->win_frequency ?? 1000;

        // Generate random number between 1 and frequency
        $randomNumber = random_int(1, $frequency);

        // Win if random number equals 1 (1 in frequency chance)
        $isWinner = ($randomNumber === 1);

        // You can add additional logic here to make wins even scarcer
        // For example, require multiple conditions:
        // $isWinner = ($randomNumber === 1) && ($this->consecutive_losses > 10);

        Log::info("Win determination for player {$this->user_id}: " .
            "Random number: {$randomNumber}/{$frequency}, " .
            "Result: " . ($isWinner ? 'WIN' : 'LOSE'));

        return $isWinner;
    }

    // Advanced win determination with multiple rarity levels
    public function determineWinAdvanced(): array
    {
        if (!$this->canWin()) {
            return [
                'is_winner' => false,
                'rarity_level' => 'none',
                'probability' => 0
            ];
        }

        // Define rarity levels (1 in X chance)
        $rarityLevels = [
            'common' => 100,     // 1% chance
            'rare' => 1000,       // 0.1% chance
            'epic' => 10000,      // 0.01% chance
            'legendary' => 100000 // 0.001% chance
        ];

        $randomNumber = random_int(1, 100000); // Max rarity denominator

        foreach ($rarityLevels as $level => $frequency) {
            if ($randomNumber <= (100000 / $frequency)) {
                return [
                    'is_winner' => true,
                    'rarity_level' => $level,
                    'probability' => 1 / $frequency,
                    'multiplier' => $this->getMultiplierForRarity($level)
                ];
            }
        }

        return [
            'is_winner' => false,
            'rarity_level' => 'none',
            'probability' => 0,
            'multiplier' => 0
        ];
    }

    protected function getMultiplierForRarity(string $rarity): int
    {
        return match ($rarity) {
            'common' => 2,
            'rare' => 5,
            'epic' => 20,
            'legendary' => 100,
            default => 0
        };
    }

    // Update player statistics after a spin
    public function updateAfterSpin(bool $won, float $betAmount, float $winAmount = 0): void
    {
        $this->spin_count++;
        $this->total_bet_amount += $betAmount;
        $this->last_spin_at = now();

        if ($won) {
            $this->win_count++;
            $this->total_win_amount += $winAmount;
            $this->last_win_at = now();
            $this->consecutive_wins++;
            $this->consecutive_losses = 0;
        } else {
            $this->consecutive_losses++;
            $this->consecutive_wins = 0;
        }

        $this->net_profit = $this->total_win_amount - $this->total_bet_amount;
        $this->save();
    }

    // Ban a player temporarily
    public function banTemporarily(int $days): void
    {
        $this->update([
            'is_banned' => true,
            'banned_until' => now()->addDays($days)
        ]);
    }

    // Disable winning for a player
    public function disableWinning(): void
    {
        $this->update(['is_allowed' => false]);
    }

    // Enable winning for a player
    public function enableWinning(): void
    {
        $this->update(['is_allowed' => true]);
    }

    // Set win frequency (1 in X chance)
    public function setWinFrequency(int $frequency): void
    {
        $this->update([
            'win_frequency' => $frequency,
            'win_probability' => 1 / $frequency
        ]);
    }

    // Scope to get only players who can win
    public function scopeAllowedToWin($query)
    {
        return $query->where('is_allowed', true)
            ->where('is_banned', false);
    }
}
