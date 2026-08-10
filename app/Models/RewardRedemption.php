<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reward_id', 'user_id', 'points_spent', 'redeemed_at'])]
class RewardRedemption extends Model
{
    protected function casts(): array
    {
        return ['redeemed_at' => 'datetime', 'points_spent' => 'integer'];
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
