<?php

namespace App\Http\Controllers;

use App\Models\AdVideo;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        if ($user->type !== 'advertiser') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only advertisers can view video orders.',
            ], 403);
        }

        $perPage = (int) $request->query('per_page', 15);
        $query = Order::query()->with([
            'user.userProfile:id,user_id,phone_number',
            'adVideo:id,title,advertiser_id',
        ])->orderByDesc('created_at');

        $query->whereHas('adVideo', function ($adVideoQuery) use ($user) {
            $adVideoQuery->where('advertiser_id', $user->id);
        });

        $orders = $query->paginate($perPage)->through(function (Order $order) {
            return [
                'id' => $order->id,
                'user_id' => $order->user_id,
                'user_phone_number' => $order->user?->userProfile?->phone_number,
                'video_id' => $order->video_id,
                'video_title' => $order->adVideo?->title,
                'quantity' => $order->quantity,
                'total_price' => $order->total_price,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $orders,
            'message' => 'Orders retrieved successfully.',
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validationRules = [
            'video_id' => 'required|uuid|exists:ad_videos,id',
            'quantity' => 'required|integer|min:1',
        ];


        $validaor = Validator::make($request->all(), $validationRules);
        if ($validaor->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validaor->errors(),
            ], 422);
        }
        $validated = $validaor->validated();
        $user = Auth::user();

        $video = AdVideo::with('productVariant')->find($validated['video_id']);
        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found.',
            ], 404);
        }

        $productVariant = $video->productVariant->first();
        if (!$productVariant) {
            return response()->json([
                'success' => false,
                'message' => 'No product variant found for this video.',
            ], 404);
        }

        $unitPrice = (float) $productVariant->price;
        $totalPrice = $unitPrice * (int) $validated['quantity'];

        $order = Order::create([
            'user_id' => $user->id,
            'video_id' => $validated['video_id'],
            'quantity' => $validated['quantity'],
            'total_price' => $totalPrice,
        ]);
        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ],
            'message' => 'Order created successfully.',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order = Order::where('id', $order->id)->with('adVideo')->first();
        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order retrieved successfully.',
        ], 200);
    }


    public function my_orders(Request $request)
    {
        $user = Auth::user();
        $orders = Order::where('user_id', $user->id)->with('adVideo')->get();
        return response()->json([
            'success' => true,
            'data' => $orders,
            'message' => 'User orders retrieved successfully.',
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, order $order)
    {
        $validationRules = [
            'video_id' => 'required|uuid|exists:ad_videos,id',
            'quantity' => 'required|integer|min:1',
        ];
        $validaor = Validator::make($request->all(), $validationRules);
        if ($validaor->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validaor->errors(),
            ], 422);
        }

        $validated = $validaor->validated();
        $video = AdVideo::with('productVariant')->find($validated['video_id']);
        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found.',
            ], 404);
        }

        $productVariant = $video->productVariant->first();
        if (!$productVariant) {
            return response()->json([
                'success' => false,
                'message' => 'No product variant found for this video.',
            ], 404);
        }

        $unitPrice = (float) $productVariant->price;
        $totalPrice = $unitPrice * (int) $validated['quantity'];

        $order->update([
            'video_id' => $validated['video_id'],
            'quantity' => $validated['quantity'],
            'total_price' => $totalPrice,
        ]);
        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order->fresh('adVideo'),
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ],
            'message' => 'Order updated successfully.',
        ], 200);
    }

    public function deleteAllOrdersForUser(Request $request)
    {
        $user = Auth::user();
        $deletedCount = Order::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} orders deleted successfully for user.",
        ], 200);
    }

    public function delete_order_by_id(Request $request, $orderId)
    {
        $order = Order::where('id', $orderId)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or does not belong to the user.',
            ], 404);
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully.',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        $order->delete();
        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully.',
        ], 200);
    }
}
