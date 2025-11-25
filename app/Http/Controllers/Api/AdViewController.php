<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdVideo;
use App\Models\AdView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdViewController extends Controller
{
    public function track(Request $request, AdVideo $ad)
    {
        $request->validate([
            'watched_percentage' => 'required|integer|min:0|max:100'
        ]);

        $user = $request->user();
        $percentage = $request->watched_percentage;

        // Atomic + Safe from race condition
        $view = DB::transaction(function () use ($ad, $user, $percentage) {
            return AdView::updateOrCreate(
                [
                    'ad_id' => $ad->id,
                    'user_id' => $user->id
                ],
                [
                    'watched_percentage' => $percentage,
                    'viewed_at' => now()
                ]
            );
        });

        // GIVE POINTS ONLY ONCE WHEN ≥90%
        if ($percentage >= 90 && !$view->rewarded) {
            $user->increment('points', 5);
            $ad->increment('view_count');
            
            $view->update(['rewarded' => true]);

            return response()->json([
                'success' => true,
                'rewarded' => true,
                'points_earned' => 5,
                'total_points' => $user->fresh()->points,
                'message' => 'Full view completed! +5 points earned'
            ]);
        }

        return response()->json([
            'success' => true,
            'rewarded' => false,
            'watched_percentage' => $percentage,
            'message' => 'View tracked'
        ]);
    }

    // Get current user points
    public function points(Request $request)
    {
        return response()->json([
            'points' => $request->user()->points
        ]);
    }
}