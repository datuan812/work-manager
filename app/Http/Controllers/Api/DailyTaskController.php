<?php

namespace App\Http\Controllers\Api;

use App\Actions\CompleteDailyTaskAction;
use App\Http\Controllers\Controller;
use App\Models\DailyTask;
use App\Services\PointService;
use App\Services\StreakService;

class DailyTaskController extends Controller
{
    public function complete(DailyTask $dailyTask, CompleteDailyTaskAction $action, PointService $points, StreakService $streaks)
    {
        abort_unless($dailyTask->user->isChild(), 404);

        $result = $action->complete($dailyTask);

        return [
            ...$result,
            'points' => $points->balance($dailyTask->user),
            'streak' => $streaks->currentStreak($dailyTask->user),
        ];
    }

    public function uncomplete(DailyTask $dailyTask, CompleteDailyTaskAction $action, PointService $points, StreakService $streaks)
    {
        abort_unless($dailyTask->user->isChild(), 404);

        $result = $action->uncomplete($dailyTask);

        return [
            ...$result,
            'points' => $points->balance($dailyTask->user),
            'streak' => $streaks->currentStreak($dailyTask->user),
        ];
    }
}
