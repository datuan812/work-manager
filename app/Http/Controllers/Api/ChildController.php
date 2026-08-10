<?php

namespace App\Http\Controllers\Api;

use App\Enums\DailyTaskStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\DailyTask;
use App\Models\Reward;
use App\Models\User;
use App\Services\DailyTaskGenerator;
use App\Services\PointService;
use App\Services\RewardRedemptionService;
use App\Services\StreakService;

class ChildController extends Controller
{
    public function index()
    {
        return User::query()
            ->where('role', UserRole::CHILD)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'avatar', 'date_of_birth'])
            ->map(fn (User $child) => $this->childPayload($child));
    }

    public function today(User $user, DailyTaskGenerator $generator, PointService $points, StreakService $streaks)
    {
        abort_unless($user->isChild() && $user->is_active, 404);

        $generator->generateForChild($user, today());

        $dailyTasks = DailyTask::query()
            ->with('task.category')
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->orderBy('id')
            ->get();

        $completed = $dailyTasks->where('status', DailyTaskStatus::COMPLETED)->count();

        return [
            'child' => $this->childPayload($user),
            'date' => today()->toDateString(),
            'tasks' => $dailyTasks->map(fn (DailyTask $dailyTask) => $this->dailyTaskPayload($dailyTask)),
            'progress' => [
                'completed' => $completed,
                'total' => $dailyTasks->count(),
                'percent' => $dailyTasks->count() ? (int) round($completed / $dailyTasks->count() * 100) : 0,
            ],
            'points' => $points->balance($user),
            'streak' => $streaks->currentStreak($user),
            'achievements' => $user->achievements()->get()->map(fn ($achievement) => [
                'id' => $achievement->id,
                'code' => $achievement->code,
                'title' => $achievement->title,
                'description' => $achievement->description,
                'icon' => $achievement->icon,
                'unlocked_at' => $achievement->pivot->unlocked_at,
            ]),
        ];
    }

    public function progress(User $user, PointService $points, StreakService $streaks)
    {
        abort_unless($user->isChild(), 404);

        return [
            'points' => $points->balance($user),
            'streak' => $streaks->currentStreak($user),
            'completed_tasks' => $user->dailyTasks()->where('status', DailyTaskStatus::COMPLETED->value)->count(),
        ];
    }

    public function achievements(User $user)
    {
        abort_unless($user->isChild(), 404);

        return $user->achievements()->get()->map(fn ($achievement) => [
            'id' => $achievement->id,
            'code' => $achievement->code,
            'title' => $achievement->title,
            'description' => $achievement->description,
            'icon' => $achievement->icon,
            'unlocked_at' => $achievement->pivot->unlocked_at,
        ]);
    }

    public function rewards(User $user, PointService $points)
    {
        abort_unless($user->isChild(), 404);

        return [
            'points' => $points->balance($user),
            'rewards' => Reward::query()->where('is_active', true)->orderBy('required_points')->get(),
        ];
    }

    public function redeem(User $user, Reward $reward, RewardRedemptionService $redemptions)
    {
        abort_unless($user->isChild(), 404);

        return ['redemption' => $redemptions->redeem($user, $reward)];
    }

    private function childPayload(User $child): array
    {
        return [
            'id' => $child->id,
            'name' => $child->name,
            'avatar' => $child->avatar,
            'date_of_birth' => $child->date_of_birth?->toDateString(),
        ];
    }

    private function dailyTaskPayload(DailyTask $dailyTask): array
    {
        return [
            'id' => $dailyTask->id,
            'date' => $dailyTask->date->toDateString(),
            'status' => $dailyTask->status->value,
            'completed_at' => $dailyTask->completed_at?->toIso8601String(),
            'task' => [
                'id' => $dailyTask->task->id,
                'title' => $dailyTask->task->title,
                'description' => $dailyTask->task->description,
                'icon' => $dailyTask->task->icon,
                'points' => $dailyTask->task->points,
                'category' => $dailyTask->task->category ? [
                    'id' => $dailyTask->task->category->id,
                    'name' => $dailyTask->task->category->name,
                    'icon' => $dailyTask->task->category->icon,
                    'color' => $dailyTask->task->category->color,
                ] : null,
            ],
        ];
    }
}
