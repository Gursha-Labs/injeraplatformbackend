<?php

namespace App\Http\Controllers;

use App\Models\Rewards;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RewardsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}



    /**
     * Display the specified resource.
     */
    public function show(Rewards $rewards)
    {
        $reward = Rewards::all();
        return response()->json($reward);
    }

    public function showOne($id)
    {
        $reward = Rewards::find($id);
        if (!$reward) {
            return response()->json(['message' => 'Reward not found'], 404);
        }
        return response()->json($reward);
    }
    /**
     * Update the specified resource in storage.
     */
public function update(Request $request, $id)
{
    $reward = Rewards::find($id);

    if (!$reward) {
        return response()->json(['error' => 'Reward not found'], 404);
    }

    $rules = [
        'name' => 'sometimes|string|max:255',
        'description' => 'sometimes|string',
        'icon' => 'sometimes|string|max:255',
        'probability' => 'sometimes|numeric|min:0|max:100',
        'type' => 'sometimes|string|max:50',
        'value' => 'sometimes|integer|min:0',
        'is_active' => 'sometimes|boolean',
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $reward->update($validator->validated());

    return response()->json($reward);
}
}
