<?php

namespace App\Http\Controllers;

use App\Models\Game_player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Game_playerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Game_Player $game_player)
    {
        //
    }
// In your SpinWheel or SlotMachine controller
public function spin(Request $request)
{
    $user = auth()->user();
    $gameId = $request->game_id;
    $betAmount = $request->bet_amount;

    // Get or create game player record
    $gamePlayer = Game_Player::firstOrCreate([
        'user_id' => $user->id,
        'game_id' => $gameId
    ]);

    // Check if player is active and not banned (but ALLOWED to play even if is_allowed=false)
    if (!$gamePlayer->is_active || $gamePlayer->is_banned) {
        return response()->json(['error' => 'You are not allowed to play this game'], 403);
    }

    // Determine if this spin wins - but only if is_allowed is true
    $isWinner = false;
    $winResult = [
        'is_winner' => false,
        'rarity_level' => 'none',
        'probability' => 0,
        'multiplier' => 0
    ];
    
    // ONLY check for win if is_allowed is true
    if ($gamePlayer->is_allowed) {
        $winResult = $gamePlayer->determineWinAdvanced();
        $isWinner = $winResult['is_winner'];
    } else {
        // Log that player is not allowed to win
     Log::info("Player {$user->id} is not allowed to win (is_allowed=false), but can still play");
    }
    
    // Calculate win amount based on bet and rarity
    $winAmount = $isWinner 
        ? $betAmount * $winResult['multiplier'] 
        : 0;

    // Update player statistics
    $gamePlayer->updateAfterSpin($isWinner, $betAmount, $winAmount);

    return response()->json([
        'is_winner' => $isWinner,
        'win_amount' => $winAmount,
        'rarity' => $winResult['rarity_level'] ?? 'none',
        'is_allowed' => $gamePlayer->is_allowed, // Return this so frontend knows
        'message' => $isWinner 
            ? "Congratulations! You won {$winAmount}!" 
            : "Better luck next time!"
    ]);
}
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Game_Player $game_player)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Game_Player $game_player)
    {
        //
    }
}
