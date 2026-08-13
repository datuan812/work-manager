<?php

namespace App\Services;

use App\Enums\PointTransactionType;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class RewardRedemptionService
{
    public function __construct(private readonly PointService $points) {}

    public function redeem(User $child, Reward $reward): RewardRedemption
    {
        if (! $reward->is_active) {
            throw ValidationException::withMessages(['reward' => 'Reward is not available.']);
        }

        return DB::transaction(function () use ($child, $reward): RewardRedemption {
            if ($this->points->balance($child) < $reward->required_points) {
                throw ValidationException::withMessages(['points' => 'Không đủ điểm để đổi phần thưởng này.']);
            }

            $redemption = RewardRedemption::create([
                'user_id' => $child->id,
                'reward_id' => $reward->id,
                'points_spent' => $reward->required_points,
                'redeemed_at' => now(),
            ]);

            $this->points->record(
                $child,
                -$reward->required_points,
                PointTransactionType::REWARD_REDEEMED,
                RewardRedemption::class,
                $redemption->id,
                "Redeemed {$reward->title}",
            );

            return $redemption->load('reward');
        });
    }
}
