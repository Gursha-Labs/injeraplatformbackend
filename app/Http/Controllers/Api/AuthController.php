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
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * OTP expiration window in minutes
     */
    private int $otpTtlMinutes = 5;

    /**
     * Unified registration with email OTP verification
     */
public function register(Request $request)
{
    try {
        $request->validate([
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users')->where(function ($query) {
                    return $query->whereNotNull('email_verified_at');
                })
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('users')->where(function ($query) {
                    return $query->whereNotNull('email_verified_at');
                })
            ],
            'password' => 'required|string|min:6',
            'type' => 'required|in:user,advertiser',
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors(),
        ], 422);
    }

    DB::beginTransaction();

    try {
        $existingUnverifiedUser = User::where(function ($query) use ($request) {
            $query->where('email', strtolower(trim($request->email)))
                  ->orWhere('username', $request->username);
        })->whereNull('email_verified_at')->first();

        if ($existingUnverifiedUser) {
            $user = $existingUnverifiedUser;

            if ($user->email !== strtolower(trim($request->email))) {
                $emailExists = User::where('email', strtolower(trim($request->email)))
                    ->whereNotNull('email_verified_at')
                    ->exists();

                if ($emailExists) {
                    return response()->json([
                        'message' => 'Email already registered',
                        'error' => 'The email address is already registered by another user.'
                    ], 409);
                }
            }

            if ($user->username !== $request->username) {
                $usernameExists = User::where('username', $request->username)
                    ->whereNotNull('email_verified_at')
                    ->exists();

                if ($usernameExists) {
                    return response()->json([
                        'message' => 'Username already taken',
                        'error' => 'The username is already taken by another user.'
                    ], 409);
                }
            }

            $user->update([
                'username' => $request->username,
                'email' => strtolower(trim($request->email)),
                'password' => Hash::make($request->password),
                'type' => $request->type,
            ]);
        } else {
            $emailExists = User::where('email', strtolower(trim($request->email)))
                ->whereNotNull('email_verified_at')
                ->exists();

            if ($emailExists) {
                return response()->json([
                    'message' => 'Email already registered',
                    'error' => 'The email address is already registered. Please use a different email or try logging in.'
                ], 409);
            }

            $usernameExists = User::where('username', $request->username)
                ->whereNotNull('email_verified_at')
                ->exists();

            if ($usernameExists) {
                return response()->json([
                    'message' => 'Username already taken',
                    'error' => 'The username is already taken. Please choose a different username.'
                ], 409);
            }

            $user = User::create([
                'username' => $request->username,
                'email' => strtolower(trim($request->email)),
                'password' => Hash::make($request->password),
                'type' => $request->type,
            ]);
        }

        if ($user->type === 'advertiser') {
            AdvertiserProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => 'Pending',
                    'phone_number' => 'Pending',
                    'country' => 'Pending',
                    'city' => 'Pending',

                    'subscription_plan' => 'free',
                    'subscription_active' => false,
                    'account_status' => 'pending',

                    'notifications_enabled' => true,
                    'email_notifications' => true,

                    'total_ads_uploaded' => 0,
                    'total_ad_views' => 0,
                    'total_ad_likes' => 0,
                    'total_ad_shares' => 0,
                    'total_spent' => 0,

                    'is_active' => true,
                ]
            );
        }

        $otp = random_int(100000, 999999);
        $expiresAt = now()->addMinutes($this->otpTtlMinutes);

        DB::table('email_verification_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => (string) $otp,
                'created_at' => now(),
                'expires_at' => $expiresAt,
            ]
        );

        try {
            Mail::send('emails.verify-otp', [
                'otp' => $otp,
                'username' => $user->username
            ], function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Verify Your Email - Injera Platform');
            });
        } catch (Exception $e) {
            Log::error('Verification OTP email failed: ' . $e->getMessage());
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to send verification email',
                'error' => 'Unable to send OTP. Please try again later.'
            ], 500);
        }

        DB::commit();

        return response()->json([
            'message' => 'Registration initiated. An OTP has been sent to your email.',
            'user' => $user->only(['id', 'username', 'email', 'type']),
            'requires_verification' => true,
        ], 201);
    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Registration failed: ' . $e->getMessage());

        return response()->json([
            'message' => 'Registration failed',
            'error' => 'An unexpected error occurred. Please try again.'
        ], 500);
    }
}

    public function verifyEmail(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'otp' => 'required|digits:6',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        $email = strtolower(trim($request->email));

        $record = DB::table('email_verification_tokens')
            ->where('email', $email)
            ->where('token', $request->otp)
            ->first();

        if (!$record) {
            return response()->json([
                'error' => 'Invalid OTP',
                'message' => 'The provided OTP is invalid.'
            ], 400);
        }

        // CHECK EXPIRY — NO AUTO-RESEND
        if ($record->expires_at && Carbon::parse($record->expires_at)->isPast()) {
            // Just delete expired token
            DB::table('email_verification_tokens')->where('email', $email)->delete();
            return response()->json([
                'error' => 'Expired OTP',
                'message' => 'The OTP has expired. Please request a new one.'
            ], 400);
        }

        try {
            // VALID OTP → VERIFY USER
            $user = User::where('email', $email)->firstOrFail();
            
            if ($user->email_verified_at) {
                return response()->json([
                    'message' => 'Email already verified',
                    'error' => 'This email has already been verified. Please log in.'
                ], 400);
            }
            
            $user->email_verified_at = now();
            $user->save();

            DB::table('email_verification_tokens')->where('email', $email)->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Email verified successfully',
                'user' => $user->only(['id', 'username', 'email', 'type']),
                'token' => $token,
            ], 200);
        } catch (Exception $e) {
            Log::error('Email verification failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Verification failed',
                'error' => 'An unexpected error occurred. Please try again.'
            ], 500);
        }
    }

    /**
     * Resend verification OTP
     */
    public function resendVerification(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        $email = strtolower(trim($request->email));
        
        return $this->resendVerificationOtp($email);
    }

    private function resendVerificationOtp($email)
    {
        try {
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                return response()->json([
                    'error' => 'User not found',
                    'message' => 'No user found with this email address.'
                ], 404);
            }

            if ($user->email_verified_at) {
                return response()->json([
                    'message' => 'Email already verified',
                    'error' => 'This email has already been verified. Please log in.'
                ], 400);
            }

            // Generate new OTP
            $otp = random_int(100000, 999999);
            $expiresAt = now()->addMinutes($this->otpTtlMinutes);

            DB::table('email_verification_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => (string) $otp,
                    'created_at' => now(),
                    'expires_at' => $expiresAt,
                ]
            );

            try {
                Mail::send('emails.verify-otp', [
                    'otp' => $otp,
                    'username' => $user->username
                ], function ($message) use ($user) {
                    $message->to($user->email)->subject('Your New OTP - Injera Platform');
                });

                return response()->json([
                    'message' => 'New OTP sent successfully!',
                    'user' => $user->only(['id', 'username', 'email', 'type']),
                    'requires_verification' => true
                ], 200);
            } catch (Exception $e) {
                Log::error('Resend OTP failed: ' . $e->getMessage());
                return response()->json([
                    'message' => 'Failed to send OTP',
                    'error' => 'Unable to send OTP. Please try again later.'
                ], 500);
            }
        } catch (Exception $e) {
            Log::error('Resend verification failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Resend verification failed',
                'error' => 'An unexpected error occurred. Please try again.'
            ], 500);
        }
    }

    /**
     * Login (by username or email)
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'login'    => 'required|string',
                'password' => 'required',
            ]);

            $identifier = trim($request->login);
            $user = filter_var($identifier, FILTER_VALIDATE_EMAIL)
                ? User::where('email', strtolower($identifier))->first()
                : User::where('username', $identifier)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Invalid credentials',
                    'error' => 'The provided credentials are incorrect.'
                ], 401);
            }

            // Check if email is verified
            if (is_null($user->email_verified_at)) {
                return response()->json([
                    'message' => 'Please verify your email to continue.',
                    'requires_verification' => true,
                    'user' => $user->only(['id', 'username', 'email', 'type']),
                ], 403);
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
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Login failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Login failed',
                'error' => 'An unexpected error occurred. Please try again.'
            ], 500);
        }
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        try {
            return response()->json([
                'user' => $request->user()
            ]);
        } catch (Exception $e) {
            Log::error('Get user profile failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve user profile',
                'error' => 'An unexpected error occurred. Please try again.'
            ], 500);
        }
    }

    /**
     * Logout (revoke current token)
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logged out successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Logout failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Logout failed',
                'error' => 'An unexpected error occurred. Please try again.'
            ], 500);
        }
    }

    /**
     * Send password reset link to email
     */
    public function forget(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            // Generate a 6-digit OTP
            $otp = random_int(100000, 999999);

            // Normalize email casing
            $email = strtolower(trim($request->email));

            // Resend cooldown: disallow new OTP within 60 seconds
            $existing = DB::table('password_reset_tokens')->where('email', $email)->first();
            if ($existing) {
                $createdAt = Carbon::parse($existing->created_at);
                $cooldownEnds = $createdAt->addSeconds(60);
                if (Carbon::now()->lt($cooldownEnds)) {
                    $retryAfter = Carbon::now()->diffInSeconds($cooldownEnds) + 1;
                    return response()->json([
                        'message' => 'OTP recently sent. Please wait before requesting a new OTP.',
                        'retry_after' => $retryAfter
                    ], 429);
                }
            }

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => (string) $otp,
                    'created_at' => Carbon::now()
                ]
            );

            try {
                $user = User::where('email', $email)->first();
                $username = $user?->username ?? 'User';
                Mail::send('emails.password-otp', ['otp' => $otp, 'username' => $username], function ($message) use ($email) {
                    $message->to($email)
                            ->subject('Password Reset OTP - Injera Platform');
                });

                return response()->json([
                    'message' => 'Password reset OTP sent successfully'
                ], 200);
            } catch (Exception $e) {
                Log::error('Password reset OTP email failed: ' . $e->getMessage());
                return response()->json([
                    'message' => 'Failed to send password reset OTP',
                    'error' => 'Unable to send OTP. Please try again later.'
                ], 500);
            }
        } catch (Exception $e) {
            Log::error('Forget password failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Password reset failed',
                'error' => 'An unexpected error occurred. Please try again.'
            ], 500);
        }
    }

    /**
     * Reset password using email OTP
     */
    public function reset(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'otp' => 'required|digits:6',
                'password' => 'required|min:6|confirmed',
                'password_confirmation' => 'required'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            // Normalize email casing
            $email = strtolower(trim($request->email));

            $record = DB::table('password_reset_tokens')->where([
                'email' => $email,
                'token' => $request->otp,
            ])->first();

            if (!$record) {
                return response()->json([
                    'error' => 'Invalid OTP',
                    'message' => 'The provided OTP is invalid.'
                ], 400);
            }

            // Check expiry (valid for $otpTtlMinutes minutes)
            $createdAt = Carbon::parse($record->created_at);
            if ($createdAt->lt(Carbon::now()->subMinutes($this->otpTtlMinutes))) {
                DB::table('password_reset_tokens')->where(['email' => $email])->delete();
                return response()->json([
                    'error' => 'Expired OTP',
                    'message' => 'The OTP has expired. Please request a new one.'
                ], 400);
            }

            User::where('email', $email)->update([
                'password' => Hash::make($request->password)
            ]);

            // Invalidate OTP after successful reset
            DB::table('password_reset_tokens')->where(['email' => $email])->delete();

            return response()->json([
                'message' => 'Password reset successfully'
            ], 200);
        } catch (Exception $e) {
            Log::error('Password reset failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Password reset failed',
                'error' => 'An unexpected error occurred. Please try again.'
            ], 500);
        }
    }

    /**
     * Change Password (Authenticated User)
     * Requires old password + new password
     */
    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required|string',
                'password'          => 'required|string|min:6|confirmed',
                'password_confirmation' => 'required'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $user = $request->user();

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'error' => 'Incorrect current password',
                    'message' => 'The current password you entered is wrong.'
                ], 400);
            }

            // Check if new password is same as current password
            if (Hash::check($request->password, $user->password)) {
                return response()->json([
                    'error' => 'Invalid new password',
                    'message' => 'New password cannot be the same as current password.'
                ], 400);
            }

            // Update password
            $user->password = Hash::make($request->password);
            $user->save();

            // Optional: Revoke all tokens so user must re-login (more secure)
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Password changed successfully! Please log in again.',
                'requires_relogin' => true
            ], 200);
        } catch (Exception $e) {
            Log::error('Change password failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Password change failed',
                'error' => 'An unexpected error occurred. Please try again.'
            ], 500);
        }
    }
}