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
     * Get all comments + replies for an ad
     */
    public function index(AdVideo $ad)
    {
        $comments = $ad->comments()
            ->with(['user:id,username,profile_picture', 'replies.user:id,username,profile_picture'])
            ->select('id', 'ad_id', 'user_id', 'comment', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20); 
    
        return response()->json([
            'success' => true,
            'message' => 'Comments loaded',
            'ad_id' => $ad->id,
            'comment_count' => $ad->comment_count,
            'data' => $comments
        ]);
    }

    // ADD COMMENT — ALL USERS
    public function comment(Request $request, AdVideo $ad)
    {
        $request->validate(['comment' => 'required|string|max:1000']);
        $user = $request->user();

        $comment = $ad->comments()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'comment' => $request->comment
        ]);

        $ad->increment('comment_count');

        return response()->json([
            'message' => 'Comment added!',
            'comment' => $comment->load('user:id,username,profile_picture')
        ]);
    }

    // REPLY TO COMMENT — ALL USERS CAN REPLY
    public function reply(Request $request, $adId, $commentId)
    {
        $request->validate(['reply' => 'required|string|max:500']);

        $user = $request->user();
        $comment = AdComment::findOrFail($commentId);
        $ad = AdVideo::findOrFail($adId);

        if ($ad->id !== $comment->ad_id) {
            return response()->json(['error' => 'Invalid comment'], 400);
        }

        $reply = AdCommentReply::create([
            'id' => (string) Str::uuid(),
            'ad_comment_id' => $comment->id,
            'user_id' => $user->id,
            'reply' => $request->reply
        ]);

        return response()->json([
            'message' => 'Reply added!',
            'reply' => $reply->load('user:id,username,profile_picture')
        ], 201);
    }

}