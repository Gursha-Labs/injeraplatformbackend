<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user->isUser()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Ensure profile exists
        $profile = $user->userProfile ?? $user->userProfile()->create();

        if ($profile->points_balance != $user->points) {
            $profile->update([
                'points_balance' => $user->points
            ]);
        }

        return response()->json([
            'profile' => $profile->fresh()
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if (!$user->isUser()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'first_name'      => 'nullable|string|max:255',
            'last_name'       => 'nullable|string|max:255',
            'phone_number'    => 'nullable|string|max:20',
            'date_of_birth'   => 'nullable|date',
            'gender'          => 'nullable|in:male,female,other,prefer_not_to_say',
            'bio'             => 'nullable|string|max:500',
            'country'         => 'nullable|string|max:100',
            'city'            => 'nullable|string|max:100',
            'address'         => 'nullable|string|max:255',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create profile if not exists
        $profile = $user->userProfile ?? $user->userProfile()->create();

        $data = $request->except('profile_picture');

        if ($request->hasFile('profile_picture')) {

            if ($profile->profile_picture) {
                Storage::disk('public')->delete($profile->profile_picture);
            }

            $data['profile_picture'] = $request
                ->file('profile_picture')
                ->store('profiles/users', 'public');
        }

        $profile->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile->fresh(),
        ]);
    }

    public function deleteProfilePicture(Request $request)
    {
        $user = $request->user();

        if (!$user->isUser()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $profile = $user->userProfile;

        if (!$profile || !$profile->profile_picture) {
            return response()->json(['message' => 'No profile picture to delete']);
        }

        Storage::disk('public')->delete($profile->profile_picture);

        $profile->update(['profile_picture' => null]);

        return response()->json([
            'message' => 'Profile picture deleted successfully'
        ]);
    }


    public function delete_account(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$user->isUser() && !$user->isAdvertiser()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $filesToDelete = [];

        if ($user->isUser() && $user->userProfile) {
            if ($user->userProfile->profile_picture) {
                $filesToDelete[] = $user->userProfile->profile_picture;
            }
        }

        if ($user->isAdvertiser() && $user->advertiserProfile) {
            foreach (['logo', 'profile_picture', 'cover_image'] as $field) {
                if (!empty($user->advertiserProfile->{$field})) {
                    $filesToDelete[] = $user->advertiserProfile->{$field};
                }
            }
        }

        DB::transaction(function () use ($user) {
            $user->delete();
        });

        foreach (array_unique($filesToDelete) as $path) {
            Storage::disk('public')->delete($path);
        }

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }



    public function withdrawal_history(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isUser()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $perPage = (int) $request->query('per_page', 15);
        $withdrawals = $user->withdrawals()
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'withdrawals' => $withdrawals
        ]);
    }
}
