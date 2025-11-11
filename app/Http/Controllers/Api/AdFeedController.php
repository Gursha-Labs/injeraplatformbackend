<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdVideo;
use Illuminate\Http\Request;

class AdFeedController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 5;
        $cursor = $request->query('cursor');
    
        $query = AdVideo::with([    
                'advertiser:id,username,profile_picture',
                'category:id,name',
                'tags:id,name',
                'comments' => function ($q) {
                    $q->with(['user:id,username,profile_picture', 'replies.advertiser:id,username,profile_picture'])
                      ->select('id', 'ad_id', 'user_id', 'comment', 'created_at');
                }
            ])
            ->select([
                'id', 'title', 'video_url', 'advertiser_id', 'category_id',
                'view_count', 'comment_count', 'duration', 'created_at'
            ])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    
        if ($cursor) {
            [$cursorDate, $cursorId] = explode('|', $cursor);
    
            $query->where(function ($q) use ($cursorDate, $cursorId) {
                $q->where('created_at', '<', $cursorDate)
                  ->orWhere(function ($q2) use ($cursorDate, $cursorId) {
                      $q2->where('created_at', '=', $cursorDate)
                         ->where('id', '<', $cursorId);
                  });
            });
        }
    
        $ads = $query->limit($perPage + 1)->get();
    
        $hasMore = $ads->count() > $perPage;
        $ads = $ads->take($perPage);
    
        $nextCursor = $hasMore
            ? $ads->last()->created_at->format('Y-m-d H:i:s') . '|' . $ads->last()->id
            : null;
    
        return response()->json([
            'data' => $ads,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore
        ]);
    }
}