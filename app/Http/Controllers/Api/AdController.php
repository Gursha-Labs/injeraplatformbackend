<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Tag;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdController extends Controller
{
    
    /**
     * Get all categories for ad upload form
     *
     * @return \Illuminate\Http\JsonResponse
     */
 protected $tagSearchable = ['name'];
protected $videoSearchable = ['title', 'description', 'video_url'];

public function search_ads(Request $request)
{
    try {
        $query = AdVideo::query()
            ->join('video_tags', 'ad_videos.id', '=', 'video_tags.video_id')
            ->join('tags', 'video_tags.tag_id', '=', 'tags.id')
            ->select('ad_videos.*')
            ->distinct();

        if ($request->filled('q')) {
            $searchTerm = $request->input('q');

            $query->where(function ($q) use ($searchTerm) {
                foreach ($this->tagSearchable as $field) {
                    $q->orWhere("tags.$field", 'LIKE', '%' . $searchTerm . '%');
                }

                foreach ($this->videoSearchable as $field) {
                    $q->orWhere("ad_videos.$field", 'LIKE', '%' . $searchTerm . '%');
                }
            });
        }

        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        
        $paginatedResults = $query->paginate($perPage, ['*'], 'page', $page);
        
        if($paginatedResults->isEmpty()){
            return response()->json([
                'success' => false,
                'message' => 'No ads found matching the search criteria.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $paginatedResults->items(),
            'pagination' => [
                'current_page' => $paginatedResults->currentPage(),
                'last_page' => $paginatedResults->lastPage(),
                'per_page' => $paginatedResults->perPage(),
                'total' => $paginatedResults->total(),
                'from' => $paginatedResults->firstItem(),
                'to' => $paginatedResults->lastItem(),
                'has_more_pages' => $paginatedResults->hasMorePages(),
                'has_previous_pages' => $paginatedResults->currentPage() > 1,
                'next_page_url' => $paginatedResults->nextPageUrl(),
                'previous_page_url' => $paginatedResults->previousPageUrl(),
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Search Ads Error: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Failed to search ads',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function getCategories()
    {
        $categories = Category::select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    /**
     * Upload a new ad video
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
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
    private function getVideoDuration($filePath)
    {
        if (!file_exists($filePath)) return null;
    
        $cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($filePath);
        $output = shell_exec($cmd);
        return $output ? (int) round(floatval(trim($output))) : null;
    }
}