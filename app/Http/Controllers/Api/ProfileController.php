<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\AdvertiserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Get authenticated user's profile
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'type' => $user->type,
                'email_verified_at' => $user->email_verified_at,
            ],
            'profile' => $profile
        ]);
    }

    /**
     * Update user profile
     */
    public function updateUserProfile(Request $request)
    {
        $user = $request->user();

        if (!$user->isUser()) {
            return response()->json([
                'message' => 'This endpoint is only for regular users'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'bio' => 'nullable|string|max:500',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'payment_methods' => 'nullable|array',
            'favorite_categories' => 'nullable|array',
            'notifications_enabled' => 'nullable|boolean',
            'email_notifications' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = $user->userProfile;
        
        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found'
            ], 404);
        }

        $data = $request->except(['profile_picture']);

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete old picture if exists
            if ($profile->profile_picture) {
                Storage::disk('public')->delete($profile->profile_picture);
            }

            $path = $request->file('profile_picture')->store('profiles/users', 'public');
            $data['profile_picture'] = $path;
        }

        $profile->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile->fresh()
        ]);
    }

    /**
     * Update advertiser profile
     */
    public function updateAdvertiserProfile(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdvertiser()) {
            return response()->json([
                'message' => 'This endpoint is only for advertisers'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'company_name' => 'nullable|string|max:255',
            'business_email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:100',
            'business_type' => 'nullable|in:individual,startup,small_business,enterprise',
            'description' => 'nullable|string|max:1000',
            'tagline' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_person_title' => 'nullable|string|max:100',
            'contact_person_phone' => 'nullable|string|max:20',
            'contact_person_email' => 'nullable|email|max:255',
            'social_media_links' => 'nullable|array',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
            'notifications_enabled' => 'nullable|boolean',
            'email_notifications' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = $user->advertiserProfile;
        
        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found'
            ], 404);
        }

        $data = $request->except(['logo', 'profile_picture', 'cover_image']);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($profile->logo) {
                Storage::disk('public')->delete($profile->logo);
            }
            $data['logo'] = $request->file('logo')->store('profiles/advertisers/logos', 'public');
        }

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            if ($profile->profile_picture) {
                Storage::disk('public')->delete($profile->profile_picture);
            }
            $data['profile_picture'] = $request->file('profile_picture')->store('profiles/advertisers/pictures', 'public');
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            if ($profile->cover_image) {
                Storage::disk('public')->delete($profile->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('profiles/advertisers/covers', 'public');
        }

        $profile->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile->fresh()
        ]);
    }

    /**
     * Get public advertiser profile (accessible by all users)
     */
    public function getAdvertiserPublicProfile($userId)
    {
        $user = \App\Models\User::find($userId);

        if (!$user || !$user->isAdvertiser()) {
            return response()->json([
                'message' => 'Advertiser not found'
            ], 404);
        }

        $profile = $user->advertiserProfile;

        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found'
            ], 404);
        }

        // Return only public information
        return response()->json([
            'advertiser' => [
                'id' => $user->id,
                'username' => $user->username,
                'company_name' => $profile->company_name,
                'logo' => $profile->logo ? Storage::url($profile->logo) : null,
                'cover_image' => $profile->cover_image ? Storage::url($profile->cover_image) : null,
                'description' => $profile->description,
                'tagline' => $profile->tagline,
                'industry' => $profile->industry,
                'website' => $profile->website,
                'city' => $profile->city,
                'country' => $profile->country,
                'social_media_links' => $profile->social_media_links,
                'is_verified' => $profile->is_verified,
                'total_ads_uploaded' => $profile->total_ads_uploaded,
                'total_ad_views' => $profile->total_ad_views,
            ]
        ]);
    }

    /**
     * Delete profile picture
     */
    public function deleteProfilePicture(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found'
            ], 404);
        }

        if ($profile->profile_picture) {
            Storage::disk('public')->delete($profile->profile_picture);
            $profile->update(['profile_picture' => null]);
        }

        return response()->json([
            'message' => 'Profile picture deleted successfully'
        ]);
    }
}
