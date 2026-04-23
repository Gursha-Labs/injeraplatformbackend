<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

   
    public function show()
    {
        $games = Game::all();
        return response()->json([
            'games' => $games
        ]);
    }
    public function get_by_id($id)
    {
        $game = Game::find($id);
        if (!$game) {
            return response()->json([
                'error' => 'Game not found'
            ], 404);
        }
        return response()->json([
            'game' => $game
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Game $game) {}

    /**
     * Remove the specified resource from storage.
     */
}
