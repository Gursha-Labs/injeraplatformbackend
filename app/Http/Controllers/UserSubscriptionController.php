<?php

namespace App\Http\Controllers;

use App\Models\UserSubscription;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Wallet;
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

            // deactivate existing active subscriptions if overlapping
            UserSubscription::where('user_id', $user->id)->where('status', 'active')->update(['status' => 'expired']);

            $us = UserSubscription::create([
                'user_id' => $user->id,
                'subscription_id' => $plan->id,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'status' => 'active',
                'payment_reference' => $request->payment_reference ?? null,
                'payment_provider' => $request->payment_provider ?? null,
                'amount_paid' => $chargeAmount,
            ]);

            // sync advertiser profile summary fields if present
            if ($user->advertiserProfile) {
                $user->advertiserProfile->update([
                    'subscription_plan' => $plan->slug ?? $plan->name,
                    'subscription_active' => true,
                    'subscription_start_date' => $startsAt,
                    'subscription_end_date' => $expiresAt,
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => 'Subscription activated',
                'subscription' => $us->load('subscription'),
                'wallet_balance' => $wallet->balance,
            ], 201);
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
