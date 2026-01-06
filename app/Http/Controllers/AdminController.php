<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdVideo;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Admin Dashboard — All Stats + Lists
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        // Only admin can access
        if ($user->type !== 'admin') {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'Only administrators can access this dashboard.'
            ], 403);
        }

        // COUNTS
        $totalUsers = User::where('type', 'user')->count();
        $totalAdvertisers = User::where('type', 'advertiser')->count();
        $totalAllUsers = User::count(); // includes admin
        $totalVideos = AdVideo::count();
        $totalViews = AdVideo::sum('view_count');
        $totalPointsDistributed = User::sum('points');

        // LISTS (with pagination)
        $allUsers = User::select('id', 'username', 'email', 'type', 'email_verified_at', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $regularUsers = User::where('type', 'user')
            ->select('id', 'username', 'email', 'email_verified_at', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $advertisers = User::where('type', 'advertiser')
            ->with('advertiserProfile:id,user_id,company_name,total_ads_uploaded,total_ad_views')
            ->select('id', 'username', 'email', 'email_verified_at', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $videos = AdVideo::with(['advertiser:id,username', 'category:id,name'])
            ->select('id', 'title', 'video_url', 'advertiser_id', 'category_id', 'view_count', 'comment_count', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'dashboard' => [
                'summary' => [
                    'total_all_users' => $totalAllUsers,
                    'total_regular_users' => $totalUsers,
                    'total_advertisers' => $totalAdvertisers,
                    'total_videos' => $totalVideos,
                    'total_views' => $totalViews,
                    'total_points_distributed' => $totalPointsDistributed,
                ],
                'lists' => [
                    'all_users' => $allUsers,
                    'regular_users' => $regularUsers,
                    'advertisers' => $advertisers,
                    'videos' => $videos,
                ]
            ]
        ]);
    }
}