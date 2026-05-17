<?php

namespace App\Http\Middleware;

use App\Models\AdVideo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPaidstatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->type !== 'advertiser') {
            return response()->json([
                'message' => 'Only advertisers can upload videos.'
            ], 403);
        }

        $activeSubscription = $user->activeSubscription()->with('subscription')->first();

        if (!$activeSubscription || !$activeSubscription->subscription) {
            return response()->json([
                'message' => 'You need an active subscription before uploading ads.'
            ], 403);
        }

        if ($activeSubscription->subscription->is_active === false) {
            return response()->json([
                'message' => 'The selected subscription plan is not available right now.'
            ], 403);
        }

        $videoLimit = (int) $activeSubscription->subscription->video_upload_limit;
        $currentUploads = AdVideo::where('advertiser_id', $user->id)
            ->where('created_at', '>=', $activeSubscription->starts_at)
            ->count();

        if ($videoLimit > 0 && $currentUploads >= $videoLimit) {
            return response()->json([
                'message' => 'Please subscribe to upload more videos.'
            ], 403);
        }

        return $next($request);
    }
}
