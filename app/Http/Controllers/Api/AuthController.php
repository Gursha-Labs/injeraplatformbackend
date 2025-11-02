<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\AdvertiserProfile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{
    /**
     * Register a new regular user
     */
    public function registerUser(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:100|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'username' => $request->username,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'type'     => 'user',
            ]);

            // Create user profile
            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => $request->phone_number,
                'country' => $request->country,
                'city' => $request->city,
            ]);

            DB::commit();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'user'    => [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'email'    => $user->email,
                    'type'     => $user->type,
                ],
                'profile' => $user->fresh()->userProfile,
                'token'   => $token,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('User registration failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register a new advertiser
     */
    public function registerAdvertiser(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:100|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'company_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'business_email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:100',
            'business_type' => 'required|in:individual,startup,small_business,enterprise',
            'description' => 'nullable|string|max:1000',
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'username' => $request->username,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'type'     => 'advertiser',
            ]);

            // Create advertiser profile
            AdvertiserProfile::create([
                'user_id' => $user->id,
                'company_name' => $request->company_name,
                'phone_number' => $request->phone_number,
                'business_email' => $request->business_email ?? $request->email,
                'website' => $request->website,
                'industry' => $request->industry,
                'business_type' => $request->business_type,
                'description' => $request->description,
                'country' => $request->country,
                'city' => $request->city,
                'address' => $request->address,
                'account_status' => 'pending', // Requires admin approval
            ]);

            DB::commit();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Advertiser registered successfully. Your account is pending approval.',
                'user'    => [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'email'    => $user->email,
                    'type'     => $user->type,
                ],
                'profile' => $user->fresh()->advertiserProfile,
                'token'   => $token,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Advertiser registration failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Legacy register endpoint (backward compatibility)
     */
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'type'     => 'required|in:user,advertiser',
        ]);

        if ($request->type === 'user') {
            return $this->registerUser($request);
        } else {
            return $this->registerAdvertiser($request);
        }
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user'    => [
                'id'       => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
                'type'     => $user->type,
            ],
            'token'   => $token,
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    /**
     * Logout (revoke current token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Send password reset link to email
     */
    public function forget(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        // Generate a 6-digit OTP
        $otp = random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => (string) $otp,
                'created_at' => Carbon::now()
            ]
        );

        try {
            Mail::send('emails.password-otp', ['otp' => $otp], function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Your Password Reset OTP');
            });

            return response()->json([
                'message' => 'Password reset OTP sent successfully'
            ], 200);
        } catch (Exception $e) {
            Log::error('Password reset OTP email failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to send password reset OTP',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password using email OTP
     */
    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required'
        ]);

        $record = DB::table('password_reset_tokens')->where([
            'email' => $request->email,
            'token' => $request->otp,
        ])->first();

        if (!$record) {
            return response()->json([
                'error' => 'Invalid OTP',
                'message' => 'The provided OTP is invalid.'
            ], 400);
        }

        // Check expiry (valid for 10 minutes)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->lt(Carbon::now()->subMinutes(10))) {
            DB::table('password_reset_tokens')->where(['email' => $request->email])->delete();
            return response()->json([
                'error' => 'Expired OTP',
                'message' => 'The OTP has expired. Please request a new one.'
            ], 400);
        }

        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        // Invalidate OTP after successful reset
        DB::table('password_reset_tokens')->where(['email' => $request->email])->delete();

        return response()->json([
            'message' => 'Password reset successfully'
        ], 200);
    }
}