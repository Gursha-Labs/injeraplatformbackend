<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserActivity;
use App\Models\User;
use App\Models\AdVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $allUsers = User::orderBy('created_at', 'desc')
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

    public function block_user($userId)
    {
        $admin = Auth::user();

        // Only admin can block users
        if (!$admin || $admin->type !== 'admin') {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $user = User::findOrFail($userId);
        if ($user->type == "admin") {
            return response()->json(['error' => "Admin can't be blocked"]);
        } else {
            $user->is_blocking = true;
            $user->save();
            UserActivity::record(
                $user,
                'account_blocked',
                'Account blocked by admin.',
                ['blocked' => true],
                $admin,
                request()
            );
        }


        return response()->json(['success' => true, 'message' => 'User has been blocked.']);
    }
    public function unblock_user($userId)
    {
        $admin = Auth::user();

        // Only admin can unblock users
        if (!$admin || $admin->type !== 'admin') {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $user = User::findOrFail($userId);
        $user->is_blocking = false;
        $user->save();
        UserActivity::record(
            $user,
            'account_unblocked',
            'Account unblocked by admin.',
            ['blocked' => false],
            $admin,
            request()
        );

        return response()->json(['success' => true, 'message' => 'User has been unblocked.']);
    }

public function assign_role(Request $request, $userId)
{
    try {
        $admin = Auth::user();

        // ✅ Auth check
        if (!$admin || $admin->type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // ✅ Validation
        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'user', 'advertiser', 'payment_processor'])],
        ]);

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $role = $validated['role'];

        // ✅ Prevent unnecessary update
        if ($user->type === $role && $user->hasRole($role)) {
            return response()->json([
                'success' => true,
                'message' => "User already has role '{$role}'",
                'data' => $user
            ]);
        }

        DB::beginTransaction();

        // ✅ Assign role (Spatie)
        $user->syncRoles([$role]);

        // ✅ Update type (since enum now supports it)
        $user->type = $role;
        $user->save();

        // ✅ Activity log
        UserActivity::record(
            $user,
            'role_assigned',
            "Role '{$role}' was assigned by admin.",
            ['role' => $role],
            $admin,
            $request
        );

        DB::commit();

        // ✅ Reload fresh user with roles
        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => "Role '{$role}' has been assigned successfully.",
            'data' => $user
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Assign Role Error', [
            'error' => $e->getMessage(),
            'userId' => $userId
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong while assigning role',
            'error' => app()->environment('local') ? $e->getMessage() : null
        ], 500);
    }
}



    public function user_activity_log(Request $request, $userId)
    {
        $admin = Auth::user();

        if (!$admin || $admin->type !== 'admin') {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $user = User::findOrFail($userId);

        $activities = UserActivity::with('actor:id,username,email')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'last_active_at' => $user->last_active_at,
            ],
            'activities' => $activities
        ]);
    }
}
