<?php

namespace App\Http\Controllers;

use App\Models\Withdrawals;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WithdrawalsController extends BaseController
{
    private const USER_METHODS = ['telebirr', 'mpesa', 'cbe_wallet', 'cbe', 'awash_bank', 'dashen_bank', 'boa'];

    private const REVIEW_DECISIONS = ['approved', 'rejected'];

    private const TERMINAL_STATUSES = ['paid', 'failed', 'cancelled'];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $query = Withdrawals::query()->orderByDesc('created_at');

        if (!$user->hasAnyRole(['admin', 'payment_processor']) && !$user->can('view_all_withdrawals')) {
            $query->where('user_id', $user->id);
        }

        $items = $query->paginate((int) $request->query('size', 15));

        return $this->sendResponse($items, 'Withdrawals fetched');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'withdrawal_method' => 'required|string|in:' . implode(',', self::USER_METHODS),
            'account_number' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'currency' => 'nullable|string|size:3',
            'metadata' => 'nullable|array',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $withdrawal = DB::transaction(function () use ($user, $validated) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet) {
                abort(response()->json(['success' => false, 'message' => 'Wallet not found'], 404));
            }

            if ((float) $wallet->balance < (float) $validated['amount']) {
                abort(response()->json(['success' => false, 'message' => 'Insufficient balance'], 422));
            }

            $wallet->decrement('balance', $validated['amount']);

            return Withdrawals::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'withdrawal_reference' => $this->generateWithdrawalReference(),
                'amount' => $validated['amount'],
                'currency' => strtoupper($validated['currency'] ?? 'ETB'),
                // Column name follows current migration spelling.
                'witdrawal_method' => $validated['withdrawal_method'],
                'account_number' => $validated['account_number'],
                'account_name' => $validated['account_name'],
                'status' => 'pending',
                'metadata' => $validated['metadata'] ?? null,
            ]);
        });

        return $this->sendResponse($withdrawal, 'Withdrawal request created');
    }

    /**
     * Display the specified resource.
     */
    public function show(Withdrawals $withdrawal)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (
            !$user->hasAnyRole(['admin', 'payment_processor'])
            && !$user->can('view_all_withdrawals')
            && $withdrawal->user_id !== $user->id
        ) {
            return $this->sendError('Forbidden', [], 403);
        }

        return $this->sendResponse($withdrawal, 'Withdrawal fetched');
    }

    /**
     * Review withdrawal by processor.
     */
    public function update(Request $request, Withdrawals $withdrawal)
    {
        $validated = $request->validate([
            'decision' => 'required|string|in:' . implode(',', self::REVIEW_DECISIONS),
            'review_notes' => 'nullable|string|max:2000',
        ]);

        if (!in_array($withdrawal->status, ['pending', 'under_review'], true)) {
            return $this->sendError('Only pending or under_review withdrawal can be reviewed', [], 422);
        }

        $withdrawal->status = $validated['decision'];
        $withdrawal->reviewed_by = Auth::id();
        $withdrawal->reviewed_at = now();
        $withdrawal->review_notes = $validated['review_notes'] ?? null;
        $withdrawal->save();

        if ($validated['decision'] === 'rejected') {
            DB::transaction(function () use ($withdrawal) {
                $wallet = Wallet::where('id', $withdrawal->wallet_id)->lockForUpdate()->first();
                if ($wallet) {
                    $wallet->increment('balance', (float) $withdrawal->amount);
                }
            });
        }

        return $this->sendResponse($withdrawal, 'Withdrawal reviewed');
    }

    /**
     * Mark approved withdrawal as processing.
     */
    public function process(Withdrawals $withdrawal)
    {
        if ($withdrawal->status !== 'approved') {
            return $this->sendError('Only approved withdrawal can move to processing', [], 422);
        }

        $withdrawal->status = 'processing';
        $withdrawal->save();

        return $this->sendResponse($withdrawal, 'Withdrawal moved to processing');
    }

    /**
     * Complete processing as paid.
     */
    public function complete(Request $request, Withdrawals $withdrawal)
    {
        $validated = $request->validate([
            'processor_reference' => 'required|string|max:255',
            'processor_notes' => 'nullable|string|max:2000',
        ]);

        if ($withdrawal->status !== 'processing') {
            return $this->sendError('Only processing withdrawal can be marked paid', [], 422);
        }

        $withdrawal->status = 'paid';
        $withdrawal->processor_reference = $validated['processor_reference'];
        $withdrawal->processor_notes = $validated['processor_notes'] ?? null;
        $withdrawal->save();

        return $this->sendResponse($withdrawal, 'Withdrawal marked as paid');
    }

    /**
     * Mark processing as failed and refund.
     */
    public function fail(Request $request, Withdrawals $withdrawal)
    {
        $validated = $request->validate([
            'processor_notes' => 'required|string|max:2000',
        ]);

        if ($withdrawal->status !== 'processing') {
            return $this->sendError('Only processing withdrawal can be marked failed', [], 422);
        }

        DB::transaction(function () use ($withdrawal, $validated) {
            $withdrawal->status = 'failed';
            $withdrawal->processor_notes = $validated['processor_notes'];
            $withdrawal->save();

            $wallet = Wallet::where('id', $withdrawal->wallet_id)->lockForUpdate()->first();
            if ($wallet) {
                $wallet->increment('balance', (float) $withdrawal->amount);
            }
        });

        return $this->sendResponse($withdrawal->fresh(), 'Withdrawal marked as failed and refunded');
    }

    /**
     * Cancel a pending withdrawal by owner.
     */
    public function destroy(Withdrawals $withdrawal)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($withdrawal->user_id !== $user->id) {
            return $this->sendError('Forbidden', [], 403);
        }

        if (!in_array($withdrawal->status, ['pending', 'under_review'], true)) {
            return $this->sendError('Only pending or under_review withdrawal can be cancelled', [], 422);
        }

        DB::transaction(function () use ($withdrawal) {
            $withdrawal->status = 'cancelled';
            $withdrawal->save();

            $wallet = Wallet::where('id', $withdrawal->wallet_id)->lockForUpdate()->first();
            if ($wallet) {
                $wallet->increment('balance', (float) $withdrawal->amount);
            }
        });

        return $this->sendResponse($withdrawal->fresh(), 'Withdrawal cancelled and refunded');
    }

    private function generateWithdrawalReference(): string
    {
        do {
            $ref = 'wd_' . now()->format('YmdHis') . '_' . Str::upper(Str::random(6));
        } while (Withdrawals::where('withdrawal_reference', $ref)->exists());

        return $ref;
    }
}
