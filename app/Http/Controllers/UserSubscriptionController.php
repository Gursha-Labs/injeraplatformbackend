<?php

namespace App\Http\Controllers;

use App\Models\UserSubscription;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Wallet;
use App\Services\SystemBalanceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserSubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        if ($user->type === 'admin') {
            $list = UserSubscription::with(['user:id,username', 'subscription'])->orderByDesc('created_at')->paginate(25);
            return response()->json($list);
        }

        $items = UserSubscription::where('user_id', $user->id)->with('subscription')->orderByDesc('created_at')->get();
        return response()->json($items);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json(['message' => 'Not implemented'], 204);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->type !== 'advertiser') {
            return response()->json(['message' => 'Only advertisers can purchase subscriptions.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'subscription_id' => 'required|uuid|exists:subscriptions,id',
            'payment_reference' => 'nullable|string|max:255',
            'payment_provider' => 'nullable|string|max:100',
            'amount_paid' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plan = Subscription::find($request->subscription_id);
        if (!$plan) return response()->json(['message' => 'Subscription plan not found'], 404);

        $activeSubscription = UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>', now())
            ->with('subscription')
            ->latest('expires_at')
            ->first();
        if ($activeSubscription && $activeSubscription->subscription) {
            $videoLimit = (int) $activeSubscription->subscription->video_upload_limit;
            $currentUploads = \App\Models\AdVideo::where('advertiser_id', $user->id)
                ->where('created_at', '>=', $activeSubscription->starts_at)
                ->count();

            if ($videoLimit <= 0 || $currentUploads < $videoLimit) {
                return response()->json([
                    'message' => 'Already subscribed.'
                ], 409);
            }
        }

        $startsAt = $request->filled('starts_at') ? now()->parse($request->starts_at) : now();
        $expiresAt = $startsAt->copy()->addDays($plan->duration_days);
        $chargeAmount = (float) $plan->price;

        DB::beginTransaction();
        try {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet || (float) $wallet->balance < $chargeAmount) {
                DB::rollBack();
                return response()->json(['message' => 'Insufficient wallet balance. Please deposit and try again.'], 400);
            }

            $wallet->balance = (float) $wallet->balance - $chargeAmount;
            $wallet->save();

            $existingSubscription = UserSubscription::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            // keep a single current subscription row per user and refresh it on renewals
            UserSubscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->when($existingSubscription, function ($query) use ($existingSubscription) {
                    $query->where('id', '!=', $existingSubscription->id);
                })
                ->update(['status' => 'expired']);

            $subscriptionData = [
                'user_id' => $user->id,
                'subscription_id' => $plan->id,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'status' => 'active',
                'payment_reference' => $request->payment_reference ?? null,
                'payment_provider' => $request->payment_provider ?? null,
                'amount_paid' => $chargeAmount,
                'cancelled_at' => null,
            ];

            if ($existingSubscription) {
                $existingSubscription->fill($subscriptionData);
                $existingSubscription->save();
                $us = $existingSubscription;
                $wasCreated = false;
            } else {
                $us = UserSubscription::create($subscriptionData);
                $wasCreated = true;
            }

            // sync advertiser profile summary fields if present
            if ($user->advertiserProfile) {
                $user->advertiserProfile->update([
                    'subscription_plan' => $plan->slug ?? $plan->name,
                    'subscription_active' => true,
                    'subscription_start_date' => $startsAt,
                    'subscription_end_date' => $expiresAt,
                ]);
            }

            if ($chargeAmount > 0) {
                app('App\\Services\\SystemBalanceService')->recordAdded($chargeAmount, [
                    'source_type' => 'user_subscription',
                    'source_id' => $us->id,
                    'user_id' => $user->id,
                    'description' => 'Advertiser subscription payment',
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => 'Subscription activated',
                'subscription' => $us->load('subscription'),
                'wallet_balance' => $wallet->balance,
            ], $wasCreated ? 201 : 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create subscription', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(UserSubscription $userSubscription)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        if ($user->type !== 'admin' && $user->id !== $userSubscription->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($userSubscription->load('subscription'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserSubscription $userSubscription)
    {
        return response()->json(['message' => 'Not implemented'], 204);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserSubscription $userSubscription)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        // Only owner or admin may update status
        if ($user->type !== 'admin' && $user->id !== $userSubscription->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:pending,active,expired,cancelled',
        ]);
        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        if ($request->filled('status')) {
            DB::beginTransaction();
            try {
                $status = $request->status;
                $previousStatus = $userSubscription->status;

                if ($status === 'active' && $previousStatus !== 'active') {
                    $userSubscription->loadMissing('subscription');
                    $chargeAmount = (float) ($userSubscription->subscription->price ?? 0);

                    if ($chargeAmount > 0) {
                        $wallet = Wallet::where('user_id', $userSubscription->user_id)->lockForUpdate()->first();
                        if (!$wallet || (float) $wallet->balance < $chargeAmount) {
                            DB::rollBack();
                            return response()->json(['message' => 'Insufficient wallet balance. Please deposit and try again.'], 400);
                        }

                        $wallet->balance = (float) $wallet->balance - $chargeAmount;
                        $wallet->save();

                        app('App\\Services\\SystemBalanceService')->recordAdded($chargeAmount, [
                            'source_type' => 'user_subscription',
                            'source_id' => $userSubscription->id,
                            'user_id' => $userSubscription->user_id,
                            'description' => 'Advertiser subscription activation payment',
                        ]);
                    }
                }

                $userSubscription->status = $status;
                if ($status === 'cancelled') {
                    $userSubscription->cancelled_at = now();
                }
                if ($status === 'expired') {
                    $userSubscription->expires_at = now();
                }
                $userSubscription->save();

                // update advertiser profile flag
                if ($userSubscription->user && $userSubscription->user->advertiserProfile) {
                    $userSubscription->user->advertiserProfile->update([
                        'subscription_active' => $status === 'active',
                    ]);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to update subscription', 'error' => $e->getMessage()], 500);
            }
        }

        return response()->json(['message' => 'Updated', 'subscription' => $userSubscription->fresh()->load('subscription')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserSubscription $userSubscription)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        if ($user->type !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $userSubscription->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
