<?php

namespace App\Http\Controllers;

use App\Models\RecentSearch;
use Illuminate\Http\Request;

class RecentSearchController extends Controller
{
   
    public function recent_searches(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $recentSearches = RecentSearch::where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->take(5) // latest 5 searches
        ->pluck('keyword');

    return response()->json([
        'success' => true,
        'data' => $recentSearches
    ]);
}


}
