<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Tag;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\RecentSearch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

public function search_ads(Request $request, $search_term = null)
{
    try {
        $videoIdsQuery = AdVideo::query()
            ->join('video_tags', 'ad_videos.id', '=', 'video_tags.video_id')
            ->join('tags', 'video_tags.tag_id', '=', 'tags.id')
            ->select('ad_videos.id')
            ->distinct();
        
        if ($search_term) {
            // Handle recent searches for authenticated user
            if ($user = $request->user()) {
                // Avoid duplicates: if search exists, update timestamp
                RecentSearch::updateOrCreate(
                    ['user_id' => $user->id, 'keyword' => $search_term],
                    ['created_at' => now()]
                );

                // Keep only latest 5 searches
                $count = RecentSearch::where('user_id', $user->id)->count();
                if ($count > 5) {
                    $oldest = RecentSearch::where('user_id', $user->id)
                        ->orderBy('created_at', 'asc')
                        ->first();
                    $oldest->delete();
                }
            }

            $videoIdsQuery->where(function ($q) use ($search_term) {
                foreach ($this->tagSearchable as $field) {
                    $q->orWhere("tags.$field", 'LIKE', '%' . $search_term . '%');
                }

                foreach ($this->videoSearchable as $field) {
                    $q->orWhere("ad_videos.$field", 'LIKE', '%' . $search_term . '%');
                }
            });
        }

        $videoIds = $videoIdsQuery->pluck('id');
        
        if ($videoIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No ads found matching the search criteria.'
            ], 200);
        }

        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        
        $paginatedResults = AdVideo::whereIn('id', $videoIds)
            ->paginate($perPage, ['*'], 'page', $page);

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
        Log::error('Search Ads Error: ' . $e->getMessage());

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
        'tag_names.*' => 'string|max:50',
        'is_orderable' => 'nullable|boolean',
        'price' => 'required_if:is_orderable,true|nullable|numeric|min:0',
        'location' => 'required_if:is_orderable,true|nullable|string|max:255',
        'image' => 'required_if:is_orderable,true|nullable|array', // Changed to array
        'image.*' => 'image|max:5120', // Validate each image
    ]);

    $user = $request->user();
    if ($user->type !== 'advertiser') {
        return response()->json(['error' => 'Only advertisers can upload ads'], 403);
    }

    DB::beginTransaction();
    try {
        // Upload video to Cloudflare R2
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $fileName = 'ads/' . time() . '_' . Str::random(10) . '.' . $extension;
        $path = $file->storeAs('', $fileName, 'r2');
        
        $baseUrl = rtrim(env('R2_PUBLIC_URL', ''), '/');
        $videoUrl = $baseUrl ? "$baseUrl/$path" : $path;

        // Create ad video record
        $ad = AdVideo::create([
            'advertiser_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'video_url' => $videoUrl,
            'category_id' => $request->category_id,
            'is_orderable' => $request->boolean('is_orderable'),
            'duration' => $this->getVideoDuration($file->getRealPath()),
        ]);

        // Create product variant if orderable
        if ($request->is_orderable) {
            $images = [];
            
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $imageExtension = $imageFile->getClientOriginalExtension();
                    $imageName = 'product_variants/' . time() . '_' . Str::random(10) . '.' . $imageExtension;
                    $imagePath = $imageFile->storeAs('', $imageName, 'r2');
                    $images[] = $baseUrl ? "$baseUrl/$imagePath" : $imagePath;
                }
            }

            ProductVariant::create([ // Note: class name should be PascalCase
                'video_id' => $ad->id,
                'image' => !empty($images) ? json_encode($images) : null,
                'price' => $request->price,
                'location' => $request->location,
            ]);
        }

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
            'ad' => $ad->load('category', 'tags', 'productVariant')
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