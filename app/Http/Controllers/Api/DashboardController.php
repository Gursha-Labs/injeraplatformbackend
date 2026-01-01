<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AdVideo;
use App\Models\Cashout;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $type = $user->type;

        if ($type === 'user') {
            $profile = $user->userProfile;
            return response()->json([
                'type' => 'user',
                'points' => $user->points,
                'cash_balance' => $user->cash_balance ?? 0,
                'total_ads_watched' => $profile?->total_ads_watched ?? 0,
                'total_games_played' => $profile?->total_games_played ?? 0,
                'total_earned' => $profile?->total_earned ?? 0,
            ]);
        }

        if ($type === 'advertiser') {
            $profile = $user->advertiserProfile;
            return response()->json([
                'type' => 'advertiser',
                'total_ads' => $profile?->total_ads_uploaded ?? 0,
                'total_views' => $profile?->total_ad_views ?? 0,
                'total_spent' => $profile?->total_spent ?? 0,
                'subscription' => $profile?->subscription_plan ?? 'free',
                'active_ads' => $user->ads()->where('status', 'active')->count(),
            ]);
        }

        if ($type === 'admin') {
            return response()->json([
                'type' => 'admin',
                'total_users' => User::where('type', 'user')->count(),
                'total_advertisers' => User::where('type', 'advertiser')->count(),
                'total_ads' => AdVideo::count(),
                'total_views' => AdVideo::sum('view_count'),
                'total_points_distributed' => User::sum('points'),
                //'total_cashouts' => Cashout::count(),
                //'pending_cashouts' => Cashout::where('status', 'pending')->count(),
            ]);
        }

        return response()->json(['error' => 'Invalid user type'], 400);
    }
}