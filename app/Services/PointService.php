<?php

namespace App\Services;

use App\Enums\PointTransactionType;
use App\Models\PointTransaction;
use App\Models\User;

class PointService
{
    public function balance(User $child): int
    {
        return (int) $child->pointTransactions()->sum('amount');
    }

    public function record(User $child, int $amount, PointTransactionType $type, ?string $referenceType = null, ?int $referenceId = null, ?string $description = null): PointTransaction
    {
        return $child->pointTransactions()->create([
            'amount' => $amount,
            'type' => $type,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
        ]);
    }
}
