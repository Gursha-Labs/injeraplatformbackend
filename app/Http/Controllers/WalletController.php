<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function balance()
    {
        $wallet = Wallet::where('user_id', Auth::id())->first();

        return response()->json([
            'balance' => $wallet->balance ?? 0
        ]);
    }

    public function deposit(Request $request)
    {
        $wallet = Wallet::firstOrCreate([
            'user_id' => Auth::id()
        ]);

        $wallet->balance = $wallet->balance + $request->amount;
        $wallet->save();

        return response()->json([
            'message' => 'Deposit successful',
            'balance' => $wallet->balance
        ]);
    }

    public function withdraw(Request $request)
    {
        $wallet = Wallet::where('user_id', Auth::id())->first();

        if ($wallet->balance < $request->amount) {
            return response()->json([
                'error' => 'Insufficient balance'
            ], 400);
        }

        $wallet->balance = $wallet->balance - $request->amount;
        $wallet->save();

        return response()->json([
            'message' => 'Withdraw successful',
            'balance' => $wallet->balance
        ]);
    }
}
