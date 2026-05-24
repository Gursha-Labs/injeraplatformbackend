<?php

namespace App\Services;

use App\Models\SystemBalance;

class SystemBalanceService
{
    public function recordAdded(float $amount, array $context = []): SystemBalance
    {
        return $this->recordMovement('added', $amount, $context);
    }

    public function recordMinus(float $amount, array $context = []): SystemBalance
    {
        return $this->recordMovement('minus', $amount, $context);
    }

    public function getCurrentBalance(): float
    {
        $latestMovement = SystemBalance::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return $latestMovement ? (float) $latestMovement->balance_after : 0.0;
    }

    protected function recordMovement(string $movementType, float $amount, array $context): SystemBalance
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        $latestMovement = SystemBalance::query()
            ->lockForUpdate()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $balanceBefore = $latestMovement ? (float) $latestMovement->balance_after : 0.0;
        $balanceAfter = $movementType === 'added'
            ? $balanceBefore + $amount
            : $balanceBefore - $amount;

        return SystemBalance::create([
            'movement_type' => $movementType,
            'amount' => $amount,
            'source_type' => $context['source_type'] ?? null,
            'source_id' => $context['source_id'] ?? null,
            'user_id' => $context['user_id'] ?? null,
            'description' => $context['description'] ?? null,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
        ]);
    }
}
