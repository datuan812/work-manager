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
        return $this->request($child, $reward);
    }

    public function request(User $child, Reward $reward): RewardRedemption
    {
        return DB::transaction(function () use ($child, $reward): RewardRedemption {
            $lockedChild = User::query()->lockForUpdate()->findOrFail($child->id);
            $lockedReward = Reward::query()->lockForUpdate()->findOrFail($reward->id);

            if ($lockedReward->family_id !== $lockedChild->family_id) {
                throw ValidationException::withMessages([
                    'reward' => 'Phần thưởng không thuộc gia đình hiện tại.',
                ]);
            }

            if (! $lockedReward->is_active) {
                throw ValidationException::withMessages(['reward' => 'Reward is not available.']);
            }

            if ($this->points->balance($lockedChild) < $lockedReward->required_points) {
                throw ValidationException::withMessages(['points' => 'Không đủ điểm để đổi phần thưởng này.']);
            }

            return RewardRedemption::create([
                'family_id' => $lockedChild->family_id,
                'user_id' => $lockedChild->id,
                'reward_id' => $lockedReward->id,
                'points_spent' => $lockedReward->required_points,
                'status' => 'pending',
                'requested_at' => now(),
            ])->load('reward');
        });
    }

    public function approve(RewardRedemption $redemption): RewardRedemption
    {
        return DB::transaction(function () use ($redemption): RewardRedemption {
            $lockedRedemption = RewardRedemption::query()
                ->with(['reward', 'user'])
                ->lockForUpdate()
                ->findOrFail($redemption->id);

            if ($lockedRedemption->status !== 'pending') {
                throw ValidationException::withMessages(['redemption' => 'Yêu cầu này đã được xử lý.']);
            }

            $lockedChild = User::query()->lockForUpdate()->findOrFail($lockedRedemption->user_id);

            if ($this->points->balance($lockedChild) < $lockedRedemption->points_spent) {
                throw ValidationException::withMessages(['points' => 'Không đủ điểm để đổi phần thưởng này.']);
            }

            $lockedRedemption->update([
                'status' => 'approved',
                'redeemed_at' => now(),
                'approved_at' => now(),
            ]);

            $this->points->record(
                $lockedChild,
                -$lockedRedemption->points_spent,
                PointTransactionType::REWARD_REDEEMED,
                RewardRedemption::class,
                $lockedRedemption->id,
                "Redeemed {$lockedRedemption->reward?->title}",
            );

            return $lockedRedemption->refresh()->load(['reward', 'user']);
        });
    }

    public function reject(RewardRedemption $redemption): RewardRedemption
    {
        return DB::transaction(function () use ($redemption): RewardRedemption {
            $lockedRedemption = RewardRedemption::query()
                ->with(['reward', 'user'])
                ->lockForUpdate()
                ->findOrFail($redemption->id);

            if ($lockedRedemption->status !== 'pending') {
                throw ValidationException::withMessages(['redemption' => 'Yêu cầu này đã được xử lý.']);
            }

            $lockedRedemption->update([
                'status' => 'rejected',
                'rejected_at' => now(),
            ]);

            return $lockedRedemption->refresh()->load(['reward', 'user']);
        });
    }
}
