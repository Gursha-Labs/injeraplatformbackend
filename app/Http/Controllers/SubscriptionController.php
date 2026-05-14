<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subscriptions = Subscription::paginate(10);

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
            'message' => 'Subscriptions fetched successfully.',
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:subscriptions,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'duration_days' => 'required|integer|min:1',
            'video_upload_limit' => 'required|integer|min:0',
            'max_video_duration_seconds' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $subscription = Subscription::create($validated);

        return response()->json([
            'success' => true,
            'data' => $subscription,
            'message' => 'Subscription created successfully.',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Subscription $subscription)
    {
        return response()->json([
            'success' => true,
            'data' => $subscription,
            'message' => 'Subscription fetched successfully.',
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'slug' => 'string|max:255|unique:subscriptions,slug,' . $subscription->id,
            'description' => 'nullable|string',
            'price' => 'numeric|min:0',
            'currency' => 'string|max:10',
            'duration_days' => 'integer|min:1',
            'video_upload_limit' => 'integer|min:0',
            'max_video_duration_seconds' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $subscription->update($validated);

        return response()->json([
            'success' => true,
            'data' => $subscription,
            'message' => 'Subscription updated successfully.',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subscription $subscription)
    {
        $subscription->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subscription deleted successfully.',
        ], 200);
    }
}
