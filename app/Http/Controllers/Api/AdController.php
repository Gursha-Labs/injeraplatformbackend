<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdVideo;
use App\Models\AdComment;
use App\Models\AdCommentReply;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:mp4,mov,avi|max:102400', // 100MB max
            'category_id' => 'required|exists:categories,id',
            'tag_names' => 'sometimes|array',
            'tag_names.*' => 'string|max:50'
        ]);
    
        $user = $request->user();
        if ($user->type !== 'advertiser') {
            return response()->json(['error' => 'Only advertisers can upload ads'], 403);
        }
    
        DB::beginTransaction();
        try {
            // UPLOAD TO CLOUDFLARE R2 (100% FREE + CDN AUTOMATIC)
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $fileName = 'ads/' . time() . '_' . Str::random(10) . '.' . $extension;
    
            // This uploads to R2 and returns public URL via Cloudflare CDN
            $path = $file->storeAs('', $fileName, 'r2');
            // Get the base URL from config or use the direct path if URL is not available
            $baseUrl = rtrim(env('R2_PUBLIC_URL', ''), '/');
            $videoUrl = $baseUrl ? "$baseUrl/$path" : $path;
    
            // Create ad video
            $ad = AdVideo::create([
                'advertiser_id' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'video_url' => $videoUrl, // DIRECT CDN URL — SUPER FAST IN ETHIOPIA
                'category_id' => $request->category_id,
                'duration' => $this->getVideoDuration($file->getRealPath()), // temp path
            ]);
    
            // Handle tags
            if ($request->has('tag_names')) {
                $tagIds = [];
                foreach ($request->tag_names as $tagName) {
                    $tagName = trim(strtolower($tagName));
                    if (empty($tagName)) continue;
    
                    $tag = Tag::firstOrCreate(['name' => $tagName]);
                    $tagIds[] = $tag->id;
                }
                if (!empty($tagIds)) {
                    $ad->tags()->attach($tagIds);
                }
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Ad uploaded successfully to Cloudflare R2 + CDN!',
                'ad' => $ad->load('category', 'tags')
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

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
            'comment' => $comment->load('user:id,username')
        ]);
    }


    public function reply(Request $request, $adId, $commentId)
    {
        $request->validate(['reply' => 'required|string|max:500']);
    
        $user = $request->user();
        if ($user->type !== 'advertiser') {
            return response()->json(['error' => 'Only advertisers can reply'], 403);
        }
    
        $comment = AdComment::findOrFail($commentId);
        $ad = AdVideo::findOrFail($adId);
    
        if ($ad->advertiser_id !== $user->id) {
            return response()->json(['error' => 'You can only reply to comments on your ad'], 403);
        }
    
        $reply = AdCommentReply::create([
            'id' => (string) Str::uuid(),
            'ad_comment_id' => $comment->id,
            'advertiser_id' => $user->id,
            'reply' => $request->reply
        ]);
    
        return response()->json([
            'message' => 'Reply added!',
            'reply' => $reply->load('advertiser:id,username,profile_picture')
        ], 201);
    }


    public function show(AdVideo $ad)
    {
        $ad->load([
            'advertiser:id,username,profile_picture',
            'category:id,name',
            'tags:id,name',
            'comments' => function ($q) {
                $q->with(['user:id,username,profile_picture', 'replies.advertiser:id,username,profile_picture'])
                  ->select('id', 'ad_id', 'user_id', 'comment', 'created_at');
            }
        ]);
    
        return response()->json([
            'ad' => $ad
        ]);
    }

    
    private function getVideoDuration($filePath)
    {
        if (!file_exists($filePath)) return null;
    
        $cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($filePath);
        $output = shell_exec($cmd);
        return $output ? (int) round(floatval(trim($output))) : null;
    }
}