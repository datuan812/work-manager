<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToFamily;

#[Fillable(['family_id', 'reward_id', 'user_id', 'points_spent', 'status', 'requested_at', 'approved_at', 'rejected_at', 'redeemed_at'])]
class RewardRedemption extends Model
{
    use BelongsToFamily;
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'redeemed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'requested_at' => 'datetime',
            'points_spent' => 'integer',
        ];
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
