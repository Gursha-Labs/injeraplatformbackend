<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdVideo;
use App\Models\AdComment;
use App\Models\AdCommentReply;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    /**
     * Get paginated comments (20 per page) — NO REPLIES LOADED
     */
    public function index(AdVideo $ad)
    {
        $comments = $ad->comments()
            ->with('user:id,username,profile_picture')
            ->select('id', 'ad_id', 'user_id', 'comment', 'created_at', 'reply_count')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'ad_id' => $ad->id,
            'comment_count' => $ad->comment_count,
            'data' => $comments
        ]);
    }

    /**
     * Get paginated replies for a specific comment (4 per page)
     */
    public function replies(AdVideo $ad, AdComment $comment)
    {
        if ($ad->id !== $comment->ad_id) {
            return response()->json(['error' => 'Invalid comment'], 400);
        }

        $replies = $comment->replies()
            ->with('user:id,username,profile_picture')
            ->select('id', 'ad_comment_id', 'user_id', 'reply', 'created_at')
            ->orderBy('created_at', 'asc')
            ->paginate(3);

        return response()->json([
            'success' => true,
            'comment_id' => $comment->id,
            'reply_count' => $comment->replies()->count(),
            'data' => $replies
        ]);
    }

    public function comment(Request $request, AdVideo $ad)
    {
        $request->validate(['comment' => 'required|string|max:1000']);
        $user = $request->user();

        $comment = $ad->comments()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'comment' => $request->comment,
            'reply_count' => 0
        ]);

        $ad->increment('comment_count');

        return response()->json([
            'message' => 'Comment added!',
            'comment' => $comment->load('user:id,username,profile_picture')
        ]);
    }

    public function reply(Request $request, AdVideo $ad, AdComment $comment)
    {
        $request->validate(['reply' => 'required|string|max:500']);

        if ($ad->id !== $comment->ad_id) {
            return response()->json(['error' => 'Invalid comment'], 400);
        }

        $user = $request->user();

        $reply = AdCommentReply::create([
            'id' => (string) Str::uuid(),
            'ad_comment_id' => $comment->id,
            'user_id' => $user->id,
            'reply' => $request->reply
        ]);

        $comment->increment('reply_count');

        return response()->json([
            'message' => 'Reply added!',
            'reply' => $reply->load('user:id,username,profile_picture')
        ], 201);
    }
}