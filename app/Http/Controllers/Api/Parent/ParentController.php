<?php

namespace App\Http\Controllers\Api\Parent;

use App\Enums\DailyTaskStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PointService;
use App\Services\StreakService;
use Illuminate\Http\Request;

class ParentController extends Controller
{
    public function dashboard(PointService $points, StreakService $streaks)
    {
        $today = today('Asia/Ho_Chi_Minh');

        $children = User::query()
            ->where('role', UserRole::CHILD)
            ->withCount([
                'dailyTasks as today_total' => fn ($query) => $query->whereDate('date', $today)->whereIn('status', [DailyTaskStatus::PENDING->value, DailyTaskStatus::COMPLETED->value]),
                'dailyTasks as today_completed' => fn ($query) => $query->whereDate('date', $today)->where('status', DailyTaskStatus::COMPLETED->value),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (User $child) => [
                'id' => $child->id,
                'name' => $child->name,
                'avatar' => $child->avatar,
                'is_active' => $child->is_active,
                'today_completed' => $child->today_completed,
                'today_total' => $child->today_total,
                'completion_percent' => $child->today_total ? (int) round($child->today_completed / $child->today_total * 100) : 0,
                'points' => $points->balance($child),
                'streak' => $streaks->currentStreak($child),
            ]);

        return ['children' => $children];
    }

    public function statistics(Request $request)
    {
        $today = today('Asia/Ho_Chi_Minh');
        $children = User::query()->where('role', UserRole::CHILD)->get();

        return [
            'children_count' => $children->count(),
            'active_tasks' => \App\Models\Task::query()->where('is_active', true)->count(),
            'completed_today' => \App\Models\DailyTask::query()->whereDate('date', $today)->where('status', DailyTaskStatus::COMPLETED->value)->count(),
            'missed_today' => \App\Models\DailyTask::query()->whereDate('date', $today)->where('status', DailyTaskStatus::PENDING->value)->count(),
            'points_total' => \App\Models\PointTransaction::query()->sum('amount'),
        ];
    }
}
