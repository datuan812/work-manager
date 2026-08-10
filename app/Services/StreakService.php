<?php

namespace App\Services;

use App\Enums\DailyTaskStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class StreakService
{
    public function currentStreak(User $child, ?CarbonInterface $from = null): int
    {
        $date = CarbonImmutable::parse($from ?? today());
        $streak = 0;

        while (true) {
            $tasks = $child->dailyTasks()->whereDate('date', $date)->get();

            if ($tasks->isEmpty()) {
                return $streak;
            }

            if ($tasks->contains(fn ($task) => $task->status !== DailyTaskStatus::COMPLETED)) {
                return $streak;
            }

            $streak++;
            $date = $date->subDay();
        }
    }
}
