<?php

namespace App\Models;

use App\Enums\PointTransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'amount', 'type', 'reference_type', 'reference_id', 'description'])]
class PointTransaction extends Model
{
    protected function casts(): array
    {
        return ['type' => PointTransactionType::class, 'amount' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
