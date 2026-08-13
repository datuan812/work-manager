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

        $rules = [
            'first_step' => $completedCount >= 1,
            'seven_day_streak' => $streak >= 7,
            'task_master' => $completedCount >= 100,
            'perfect_day' => $perfectToday,
        ];

        Achievement::query()
            ->where('is_active', true)
            ->whereIn('code', array_keys(array_filter($rules)))
            ->get()
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
