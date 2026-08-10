<?php

namespace App\Services;

use App\Enums\DailyTaskStatus;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonInterface;

class DailyTaskGenerator
{
    public function generateForChild(User $child, CarbonInterface $date): void
    {
        Task::query()
            ->with('schedule')
            ->where('user_id', $child->id)
            ->where('is_active', true)
            ->whereHas('schedule')
            ->get()
            ->each(function (Task $task) use ($date): void {
                if (! $task->schedule?->occursOn($date)) {
                    return;
                }

                $task->dailyTasks()->firstOrCreate(
                    ['date' => $date->toDateString()],
                    [
                        'user_id' => $task->user_id,
                        'status' => DailyTaskStatus::PENDING,
                    ],
                );
            });
    }
}
