<?php

namespace App\Http\Controllers;

use App\Models\Deposite;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class DepositeController extends Controller
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
        // Validation including phone
        $validationRule = [
            "amount" => "required|numeric|min:1",
            "first_name" => "required|string",
            "last_name" => "required|string",
            "phone" => "required|string" // add phone validation
        ];

        $validatedData = $request->validate($validationRule);

        $user = Auth::user(); // get authenticated user

        $tx_ref = uniqid("tx_");

        // Create transaction with status pending
        Transaction::create([
            "user_id" => $user->id,
            "tx_ref" => $tx_ref,
            "amount" => $request->amount,
            "status" => "pending"
        ]);

        // Initialize Chapa transaction
        $response = Http::withHeaders([
            "Authorization" => "Bearer " . env("CHAPA_SECRET_KEY")
        ])->post("https://api.chapa.co/v1/transaction/initialize", [
            "amount" => $request->amount,
            "currency" => "ETB",
            "email" => $user->email,       // authenticated email
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            "phone" => $request->phone,    // send phone number
            "tx_ref" => $tx_ref,
            "callback_url" => url("/api/chapa/webhook"),
            "return_url" => "https://ngrok.com/blog/quantization/payment-success"
        ]);

        return response()->json($response->json());
    }

    public function webhook(Request $request)
    {
        $tx_ref = $request->tx_ref;
        $verify = Http::withHeaders([
            "Authorization" => "Bearer " . env("CHAPA_SECRET_KEY")
        ])->get("https://api.chapa.co/v1/transaction/verify/" . $tx_ref);

        $data = $verify->json();
        if ($data["status"] == "success") {
            $transaction = Transaction::where("tx_ref", $tx_ref)->first();

            if ($transaction && $transaction->status != "success") {
                $transaction->update([
                    "status" => "success"
                ]);

                $wallet = Wallet::firstOrCreate(
                    ["user_id" => $transaction->user_id],
                    ["balance" => 0]
                );

                $wallet->increment("balance", $transaction->amount);
            }
        }

        return response()->json(["message" => "ok"]);
    }


    /**
     * Display the specified resource.
     */
    public function show(Deposite $deposite)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Deposite $deposite)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Deposite $deposite)
    {
        //
    }
}
