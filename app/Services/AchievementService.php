<?php

namespace App\Services;

use App\Enums\DailyTaskStatus;
use App\Models\Achievement;
use App\Models\User;

class AchievementService
{
    public function evaluate(User $child): array
    {
        $unlocked = [];
        $today = today('Asia/Ho_Chi_Minh');
        $completedCount = $child->dailyTasks()->where('status', DailyTaskStatus::COMPLETED->value)->count();
        $streak = app(StreakService::class)->currentStreak($child);
        $perfectToday = $child->dailyTasks()->whereDate('date', $today)->exists()
            && ! $child->dailyTasks()->whereDate('date', $today)->where('status', '!=', DailyTaskStatus::COMPLETED->value)->exists();

        $legacyRules = [
            'first_step' => $completedCount >= 1,
            'seven_day_streak' => $streak >= 7,
            'task_master' => $completedCount >= 100,
            'perfect_day' => $perfectToday,
        ];

        Achievement::query()
            ->where('is_active', true)
            ->get()
            ->filter(function (Achievement $achievement) use ($completedCount, $streak, $perfectToday, $legacyRules): bool {
                $criteria = $achievement->criteria ?? [];

                return match ($criteria['type'] ?? null) {
                    'completed_tasks' => $completedCount >= (int) ($criteria['value'] ?? PHP_INT_MAX),
                    'streak_days' => $streak >= (int) ($criteria['value'] ?? PHP_INT_MAX),
                    'perfect_day' => $perfectToday,
                    default => $legacyRules[$achievement->code] ?? false,
                };
            })
            ->each(function (Achievement $achievement) use ($child, &$unlocked): void {
                $attached = $child->achievements()->syncWithoutDetaching([
                    $achievement->id => ['unlocked_at' => now()],
                ]);

                if (! empty($attached['attached'])) {
                    $unlocked[] = $achievement;
                }
            });

        return $unlocked;
    }
}
