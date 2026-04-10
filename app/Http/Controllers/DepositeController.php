<?php

namespace App\Http\Controllers;

use App\Models\Deposite;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepositeController extends BaseController
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
            "phone" => "required|string"
        ];

        $validatedData = $request->validate($validationRule);

        $user = Auth::user();
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
            "email" => $user->email,
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            "phone" => $request->phone,
            "tx_ref" => $tx_ref,
            "callback_url" => "https://hypergolic-erma-violably.ngrok-free.dev/api/chapa/webhook",
            "return_url" => "https://hypergolic-erma-violably.ngrok-free.dev/payment-success?tx_ref=" . $tx_ref
        ]);

        return response()->json($response->json());
    }

    /**
     * Handle Chapa webhook
     */
    public function webhook(Request $request)
    {
        // Log everything for debugging
        Log::info('Webhook received', [
            'all_data' => $request->all(),
            'tx_ref' => $request->tx_ref,
            'content' => $request->getContent()
        ]);

        $tx_ref = $request->tx_ref;

        if (!$tx_ref) {
            Log::error('No tx_ref in webhook request');
            return response()->json(['error' => 'No tx_ref'], 400);
        }

        // Retry verification up to 3 times
        $maxRetries = 3;
        $attempt = 0;
        $verified = false;
        $data = null;

        while ($attempt < $maxRetries && !$verified) {
            $attempt++;

            $verify = Http::withHeaders([
                "Authorization" => "Bearer " . env("CHAPA_SECRET_KEY")
            ])->get("https://api.chapa.co/v1/transaction/verify/" . $tx_ref);

            $data = $verify->json();

            if (isset($data["status"]) && $data["status"] == "success") {
                $verified = true;
                break;
            }

            if ($attempt < $maxRetries) {
                sleep(2); // Wait 2 seconds before retry
            }
        }

        Log::info('Chapa verification response', ['data' => $data, 'verified' => $verified]);

        if ($verified) {
            $transaction = Transaction::where("tx_ref", $tx_ref)->first();

            if (!$transaction) {
                Log::error('Transaction not found for tx_ref: ' . $tx_ref);
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            if ($transaction->status != "success") {
                // Use database transaction to ensure consistency
                DB::beginTransaction();

                try {
                    $transaction->update(["status" => "success"]);

                    $wallet = Wallet::firstOrCreate(
                        ["user_id" => $transaction->user_id],
                        ["balance" => 0]
                    );

                    $oldBalance = $wallet->balance;
                    $wallet->increment("balance", $transaction->amount);

                    Log::info('Wallet updated', [
                        'user_id' => $transaction->user_id,
                        'old_balance' => $oldBalance,
                        'amount' => $transaction->amount,
                        'new_balance' => $wallet->balance
                    ]);

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollback();
                    Log::error('Failed to update wallet: ' . $e->getMessage());
                    return response()->json(['error' => 'Database error'], 500);
                }
            }
        } else {
            Log::warning('Payment verification failed after ' . $maxRetries . ' attempts', [
                'tx_ref' => $tx_ref,
                'response' => $data
            ]);
        }

        return response()->json(["message" => "ok"]);
    }

    /**
     * Check payment status manually from success page
     */
    public function checkPaymentStatus(Request $request)
    {
        $tx_ref = $request->query('tx_ref');

        if (!$tx_ref) {
            return response()->json(['error' => 'No tx_ref'], 400);
        }

        // Check if transaction is already processed
        $transaction = Transaction::where('tx_ref', $tx_ref)->first();

        if (!$transaction) {
            // Keep polling from the success page instead of hard-failing with 404.
            return response()->json([
                'status' => 'pending',
                'message' => 'Transaction not found yet'
            ]);
        }

        if ($transaction && $transaction->status == 'success') {
            $wallet = Wallet::where('user_id', $transaction->user_id)->first();
            return response()->json([
                'status' => 'success',
                'balance' => $wallet ? $wallet->balance : 0,
                'amount' => $transaction->amount
            ]);
        }

        // If not processed, try to verify with Chapa now
        $verify = Http::withHeaders([
            "Authorization" => "Bearer " . env("CHAPA_SECRET_KEY")
        ])->get("https://api.chapa.co/v1/transaction/verify/" . $tx_ref);

        $data = $verify->json();

        if (isset($data["status"]) && $data["status"] == "success") {
            // Process the payment immediately
            if ($transaction && $transaction->status != "success") {
                DB::beginTransaction();
                try {
                    $transaction->update(["status" => "success"]);
                    $wallet = Wallet::firstOrCreate(
                        ["user_id" => $transaction->user_id],
                        ["balance" => 0]
                    );
                    $wallet->increment("balance", $transaction->amount);
                    DB::commit();

                    return response()->json([
                        'status' => 'success',
                        'balance' => $wallet->fresh()->balance,
                        'amount' => $transaction->amount
                    ]);
                } catch (\Exception $e) {
                    DB::rollback();
                    Log::error('Manual check failed: ' . $e->getMessage());
                    return response()->json(['error' => 'Database error'], 500);
                }
            }
        }

        return response()->json(['status' => 'pending']);
    }

    /**
     * Manually process payment from success page
     */
    public function processPaymentManually(Request $request)
    {
        $tx_ref = $request->input('tx_ref');

        if (!$tx_ref) {
            return response()->json(['success' => false, 'error' => 'No tx_ref'], 400);
        }

        $verify = Http::withHeaders([
            "Authorization" => "Bearer " . env("CHAPA_SECRET_KEY")
        ])->get("https://api.chapa.co/v1/transaction/verify/" . $tx_ref);

        $data = $verify->json();

        if (isset($data["status"]) && $data["status"] == "success") {
            $transaction = Transaction::where("tx_ref", $tx_ref)->first();

            if ($transaction && $transaction->status != "success") {
                DB::beginTransaction();
                try {
                    $transaction->update(["status" => "success"]);
                    $wallet = Wallet::firstOrCreate(
                        ["user_id" => $transaction->user_id],
                        ["balance" => 0]
                    );
                    $wallet->increment("balance", $transaction->amount);
                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'balance' => $wallet->fresh()->balance,
                        'amount' => $transaction->amount
                    ]);
                } catch (\Exception $e) {
                    DB::rollback();
                    Log::error('Manual processing failed: ' . $e->getMessage());
                    return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
                }
            }
        }

        return response()->json(['success' => false, 'error' => 'Verification failed']);
    }

    /**
     * Debug transaction - for troubleshooting only
     */
    public function debugTransaction($tx_ref)
    {
        $transaction = Transaction::where('tx_ref', $tx_ref)->first();

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $wallet = Wallet::where('user_id', $transaction->user_id)->first();

        // Verify with Chapa
        $chapaVerification = Http::withHeaders([
            "Authorization" => "Bearer " . env("CHAPA_SECRET_KEY")
        ])->get("https://api.chapa.co/v1/transaction/verify/" . $tx_ref);

        return response()->json([
            'transaction' => [
                'id' => $transaction->id,
                'tx_ref' => $transaction->tx_ref,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'user_id' => $transaction->user_id,
                'created_at' => $transaction->created_at
            ],
            'wallet' => $wallet ? [
                'balance' => $wallet->balance,
                'user_id' => $wallet->user_id
            ] : null,
            'chapa_verification' => $chapaVerification->json()
        ]);
    }

    /**
     * Get wallet balance for authenticated user
     */
    public function getWalletBalance(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $wallet = Wallet::where('user_id', $user->id)->first();
        return $this->sendResponse(
            [
                'balance' => $wallet->balance
            ],
            'Deposit successful',

        );
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
