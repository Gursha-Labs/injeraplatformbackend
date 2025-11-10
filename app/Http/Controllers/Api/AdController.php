<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdVideo;
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
            'file' => 'required|file|mimes:mp4,mov,avi|max:102400',
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
            // Store file
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $fileName = 'ad_' . time() . '_' . uniqid() . '.' . $extension;
            $path = $file->storeAs('ads', $fileName, 'public');
            $videoUrl = 'ads/' . $fileName;

            // Create ad video
            $ad = AdVideo::create([
                'advertiser_id' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'video_url' => $videoUrl,
                'category_id' => $request->category_id,
                'duration' => $this->getVideoDuration($file),
            ]);

            // Handle tags
            if ($request->has('tag_names')) {
                $tagIds = [];
                
                foreach ($request->tag_names as $tagName) {
                    $tagName = trim(strtolower($tagName));
                    if (empty($tagName)) continue;

                    // The HasUuid trait will automatically generate the ID
                    $tag = Tag::firstOrCreate(['name' => $tagName]);
                    $tagIds[] = $tag->id;
                }

                if (!empty($tagIds)) {
                    $ad->tags()->attach($tagIds);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Ad uploaded successfully!',
                'ad' => $ad->load('category', 'tags')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Clean up uploaded file if something went wrong
            if (isset($videoUrl) && Storage::disk('public')->exists($videoUrl)) {
                Storage::disk('public')->delete($videoUrl);
            }

            return response()->json([
                'error' => 'Failed to upload ad: ' . $e->getMessage()
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

    private function getVideoDuration($videoUrl)
    {
        $path = storage_path('app/public/' . $videoUrl);
        if (!file_exists($path)) return null;

        $cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($path);
        $output = shell_exec($cmd);
        return $output ? (int) round(floatval($output)) : null;
    }
}