<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        if ($user->type !== 'user') {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return response()->json([
            'profile' => $user->userProfile ?? null
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        if ($user->type !== 'user') {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'bio' => 'nullable|string|max:500',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $profile = $user->userProfile ?? $user->userProfile()->create(['user_id' => $user->id]);

        $data = $request->except('profile_picture');

        if ($request->hasFile('profile_picture')) {
            if ($profile->profile_picture) {
                Storage::disk('public')->delete($profile->profile_picture);
            }
            $data['profile_picture'] = $request->file('profile_picture')->store('profiles/users', 'public');
        }

        $profile->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile->fresh()
        ]);
    }

    public function deleteProfilePicture(Request $request)
    {
        $user = $request->user();
        if ($user->type !== 'user') {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $profile = $user->userProfile;
        if (!$profile || !$profile->profile_picture) {
            return response()->json(['message' => 'No profile picture to delete']);
        }

        Storage::disk('public')->delete($profile->profile_picture);
        $profile->update(['profile_picture' => null]);

        return response()->json(['message' => 'Profile picture deleted successfully']);
    }
}