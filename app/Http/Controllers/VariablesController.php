<?php

namespace App\Http\Controllers;

use App\Models\Variables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VariablesController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $variable = Variables::all();
        return response()->json($variable);
    }
    public function showOne($id)
    {
        $variable = Variables::find($id);
        if (!$variable) {
            return response()->json(['message' => 'Variable not found'], 404);
        }
        return response()->json($variable);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
     {
        $variable = Variables::find($id);
        if (!$variable) {
            return response()->json(['error' => 'Variable not found'], 404);
        }

        $rules = [
            'point' => 'required',
            'type' => 'sometimes|in:earn_point,bet_point',
            'value' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $variable->update($validator->validated());

        return response()->json($variable);
    }
   

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Variables $variables)
    {
        //
    }
}
