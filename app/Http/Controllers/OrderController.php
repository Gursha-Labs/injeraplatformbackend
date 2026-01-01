<?php

namespace App\Http\Controllers;

use App\Models\order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validationRules = [
            'video_id' => 'required|uuid|exists:ad_videos,id',
            'quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
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
        $order = order::create([
            "user_id" => $user->id,
            "video_id" => $validated['video_id'],
            "quantity" => $validated['quantity'],
            "total_price" => $validated['total_price'],
        ]);
        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order created successfully.',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(order $order)
    {
      $order = order::where('id', $order->id)->with('adVideo')->first();
        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order retrieved successfully.',
        ], 200);
       
    }


    public function my_orders(Request $request)
    {
        $user = Auth::user();
        $orders = order::where('user_id', $user->id)->with('adVideo')->get();
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
            'total_price' => 'required|numeric|min:0',
        ];
        $validaor = Validator::make($request->all(), $validationRules);
        if ($validaor->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validaor->errors(),
            ], 422);
        }
        $order->update($validaor->validated());
        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order updated successfully.',
        ], 200);
       
    }

    public function deleteAllOrdersForUser(Request $request)
    {
        $user = Auth::user();
        $deletedCount = order::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} orders deleted successfully for user.",
        ], 200);
    }

    public function delete_order_by_id(Request $request, $orderId)
    {
        $order = order::where('id', $orderId)->get();

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
    public function destroy(order $order)
    {
        $order->delete();
        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully.',
        ], 200);
    }
}
