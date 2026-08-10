<?php

namespace App\Actions;

use App\Enums\DailyTaskStatus;
use App\Enums\PointTransactionType;
use App\Models\DailyTask;
use App\Services\AchievementService;
use App\Services\PointService;
use Illuminate\Support\Facades\DB;

class CompleteDailyTaskAction
{
    public function __construct(
        private readonly PointService $points,
        private readonly AchievementService $achievements,
    ) {}

    public function complete(DailyTask $dailyTask): array
    {
        return DB::transaction(function () use ($dailyTask): array {
            $dailyTask = DailyTask::query()->with(['task.category', 'user'])->lockForUpdate()->findOrFail($dailyTask->id);

            if ($dailyTask->status === DailyTaskStatus::COMPLETED) {
                return ['daily_task' => $dailyTask, 'points_awarded' => 0, 'achievements' => []];
            }

            $dailyTask->update([
                'status' => DailyTaskStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            $this->points->record(
                $dailyTask->user,
                $dailyTask->task->points,
                PointTransactionType::TASK_COMPLETED,
                DailyTask::class,
                $dailyTask->id,
                "Completed {$dailyTask->task->title}",
            );

            return [
                'daily_task' => $dailyTask->refresh()->load('task.category'),
                'points_awarded' => $dailyTask->task->points,
                'achievements' => $this->achievements->evaluate($dailyTask->user),
            ];
        });
    }

    public function uncomplete(DailyTask $dailyTask): array
    {
        return DB::transaction(function () use ($dailyTask): array {
            $dailyTask = DailyTask::query()->with(['task.category', 'user'])->lockForUpdate()->findOrFail($dailyTask->id);

            if ($dailyTask->status !== DailyTaskStatus::COMPLETED) {
                return ['daily_task' => $dailyTask, 'points_awarded' => 0, 'achievements' => []];
            }

            $dailyTask->update([
                'status' => DailyTaskStatus::PENDING,
                'completed_at' => null,
            ]);

            $this->points->record(
                $dailyTask->user,
                -$dailyTask->task->points,
                PointTransactionType::TASK_UNCOMPLETED,
                DailyTask::class,
                $dailyTask->id,
                "Unchecked {$dailyTask->task->title}",
            );

            return ['daily_task' => $dailyTask->refresh()->load('task.category'), 'points_awarded' => -$dailyTask->task->points, 'achievements' => []];
        });
    }
}
