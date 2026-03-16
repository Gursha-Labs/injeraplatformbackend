<?php

namespace App\Http\Controllers;

use App\Models\Game_player;
use App\Models\Variables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    $user = Auth::user();
    $gameId = $request->game_id;

    $betAmount = Variables::where('type', 'bet_point')->first()->value;

    if ($betAmount > $user->point) {
        return response()->json([
            'error' => 'Insufficient points to place the bet'
        ], 400);
    }

    $gamePlayer = Game_Player::firstOrCreate([
        'user_id' => $user->id,
        'game_id' => $gameId
    ]);

    if (!$gamePlayer->is_active || $gamePlayer->is_banned) {
        return response()->json([
            'error' => 'You are not allowed to play this game'
        ], 403);
    }

    $user->point = $user->point - $betAmount;
    $user->save();

    $rewards = DB::table('rewards')
        ->where('is_active', true)
        ->get()
        ->values();

    if ($rewards->count() == 0) {
        return response()->json([
            'error' => 'No rewards configured'
        ], 500);
    }

    if (!$gamePlayer->is_allowed) {
        $selectedReward = $rewards->firstWhere('type', 'lose');
    } else {

        $totalProbability = $rewards->sum('probability');
        $rand = rand(1, $totalProbability);

        $current = 0;

        foreach ($rewards as $reward) {

            $current += $reward->probability;

            if ($rand <= $current) {
                $selectedReward = $reward;
                break;
            }
        }
    }

    $rewardIndex = $rewards->search(function ($item) use ($selectedReward) {
        return $item->id === $selectedReward->id;
    });

    $isWinner = $selectedReward->type !== 'lose';

    $winAmount = 0;

    if ($selectedReward->type == 'point') {

        $winAmount = $selectedReward->value;
        $user->point = $user->point + $winAmount;
        $user->save();

    } elseif ($selectedReward->type == 'money') {

        $winAmount = $betAmount * $selectedReward->value;
        $user->point = $user->point + $winAmount;
        $user->save();

    }

    $gamePlayer->updateAfterSpin($isWinner, $betAmount, $winAmount);

    return response()->json([
        'segment_index' => $rewardIndex,
        'reward_id' => $selectedReward->id,
        'reward_name' => $selectedReward->name,
        'reward_type' => $selectedReward->type,
        'reward_value' => $selectedReward->value,
        'is_winner' => $isWinner,
        'win_amount' => $winAmount,
        'user_points' => $user->point,
        'message' => $isWinner
            ? "You won {$selectedReward->name}"
            : "Better luck next time"
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
