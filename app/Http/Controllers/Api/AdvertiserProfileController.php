<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdVideo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdvertiserProfileController extends Controller
{
public function show(Request $request)
{
$user = Auth::user();

if ($user->type !== 'advertiser') {
    return response()->json(['error' => 'Access denied'], 403);
}

$profile = $user->advertiserProfile;

return response()->json([
// user table
'user_id' => $user->id,
'username' => $user->username,
'email' => $user->email,
'type' => $user->type,
'email_verified_at' => $user->email_verified_at,
'user_created_at' => $user->created_at,
'user_updated_at' => $user->updated_at,

// advertiser_profiles table
'advertiser_profile_id' => $profile->id ?? null,
'company_name' => $profile->company_name ?? null,
'business_email' => $profile->business_email ?? null,
'phone_number' => $profile->phone_number ?? null,
'website' => $profile->website ?? null,
'logo' => $profile->logo ?? null,
'profile_picture' => $profile->profile_picture ?? null,
'cover_image' => $profile->cover_image ?? null,
'description' => $profile->description ?? null,
'country' => $profile->country ?? null,
'city' => $profile->city ?? null,
'address' => $profile->address ?? null,
'social_media_links' => $profile->social_media_links ?? [],
'total_ads_uploaded' => $profile->total_ads_uploaded ?? 0,
'total_ad_views' => $profile->total_ad_views ?? 0,
'total_spent' => $profile->total_spent ?? "0.00",
'subscription_plan' => $profile->subscription_plan ?? null,
'subscription_active' => $profile->subscription_active ?? false,
'notifications_enabled' => $profile->notifications_enabled ?? false,
'email_notifications' => $profile->email_notifications ?? false,
'is_active' => $profile->is_active ?? false,
'last_active_at' => $profile->last_active_at ?? null,
'advertiser_created_at' => $profile->created_at ?? null,
'advertiser_updated_at' => $profile->updated_at ?? null


]);


}
    public function update(Request $request)
    {
        $user = $request->user();
        if ($user->type !== 'advertiser') {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'company_name' => 'nullable|string|max:255',
            'business_email' => 'nullable|email',
            'phone_number' => 'nullable|string|max:20',
            'website' => 'nullable|url',
            'description' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $profile = $user->advertiserProfile ?? $user->advertiserProfile()->create(['user_id' => $user->id]);

        $data = $request->except(['logo', 'profile_picture', 'cover_image']);

        if ($request->hasFile('logo')) {
            if ($profile->logo) Storage::disk('public')->delete($profile->logo);
            $data['logo'] = $request->file('logo')->store('profiles/advertisers/logos', 'public');
        }
        if ($request->hasFile('profile_picture')) {
            if ($profile->profile_picture) Storage::disk('public')->delete($profile->profile_picture);
            $data['profile_picture'] = $request->file('profile_picture')->store('profiles/advertisers/pictures', 'public');
        }
        if ($request->hasFile('cover_image')) {
            if ($profile->cover_image) Storage::disk('public')->delete($profile->cover_image);
            $data['cover_image'] = $request->file('cover_image')->store('profiles/advertisers/covers', 'public');
        }

        $profile->update($data);

        return response()->json([
            'message' => 'Advertiser profile updated successfully',
            'profile' => $profile->fresh()
        ]);
    }

    public function publicProfile($userId)
    {
        $user = User::findOrFail($userId);
        if ($user->type !== 'advertiser') {
            return response()->json(['error' => 'Not an advertiser'], 404);
        }

        $profile = $user->advertiserProfile;

        return response()->json([
            'company_name' => $profile->company_name,
            'logo' => $profile->logo ? Storage::url($profile->logo) : null,
            'cover_image' => $profile->cover_image ? Storage::url($profile->cover_image) : null,
            'description' => $profile->description,
            'website' => $profile->website,
            'city' => $profile->city,
            'address' => $profile->address,
            'country' => $profile->country,
            'total_ads' => $profile->total_ads_uploaded,
            'total_views' => $profile->total_ad_views,
        ]);
    }



  public function owen_videos(Request $request)
{
    $user = Auth::user();

    if ($user->type !== 'advertiser') {
        return response()->json(['error' => 'You are not advertiser'], 403);
    }

    $advertiser = $user->advertiserProfile;

    $perPage = $request->input('per_page', 10);

    $ads = $advertiser
        ->adVideos()
        ->orderByDesc('created_at')
        ->paginate($perPage);

    return response()->json($ads);
}


   public function get_video_by_id($id){
     
     $advido = AdVideo::findOrFail($id);

        return response()->json([
            'ad_video_id' => $advido->id,
            'title' => $advido->title,
            'description' => $advido->description,
            'video_url' => $advido->video_url,
            'thumbnail_url' => $advido->thumbnail_url,
            'views' => $advido->views->count(),
            'likes' => $advido->likes,
            'shares' => $advido->shares,
            'comments_count' => $advido->comments()->count(),
            'created_at' => $advido->created_at,
            'updated_at' => $advido->updated_at,
        ]);


   }



 
    public function deleteProfilePicture(Request $request)
    {
        $user = $request->user();
        if ($user->type !== 'advertiser') {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $profile = $user->advertiserProfile;
        if (!$profile || !$profile->profile_picture) {
            return response()->json(['message' => 'No profile picture to delete']);
        }

        Storage::disk('public')->delete($profile->profile_picture);
        $profile->update(['profile_picture' => null]);

        return response()->json(['message' => 'Profile picture deleted successfully']);
    }
}