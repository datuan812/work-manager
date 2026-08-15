<?php

namespace App\Http\Controllers\Api\Parent;

use App\Enums\DailyTaskStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\DailyTask;
use App\Models\PointTransaction;
use App\Models\RewardRedemption;
use App\Models\Task;
use App\Models\User;
use App\Services\PointService;
use App\Services\StreakService;
use Carbon\CarbonPeriod;
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
        $startDate = $today->copy()->subDays(13);
        $children = User::query()
            ->where('role', UserRole::CHILD)
            ->withCount([
                'dailyTasks as completed_tasks_count' => fn ($query) => $query->where('status', DailyTaskStatus::COMPLETED->value),
                'rewardRedemptions as reward_redemptions_count',
            ])
            ->orderBy('name')
            ->get();

        $dailyRows = DailyTask::query()
            ->selectRaw('date, status, count(*) as total')
            ->whereBetween('date', [$startDate->toDateString(), $today->toDateString()])
            ->groupBy('date', 'status')
            ->get()
            ->groupBy(fn ($row) => $row->date->toDateString());

        $dailyTrend = collect(CarbonPeriod::create($startDate, $today))
            ->map(function ($date) use ($dailyRows) {
                $key = $date->toDateString();
                $rows = $dailyRows->get($key, collect());
                $statusTotal = fn (DailyTaskStatus $status) => (int) ($rows->first(
                    fn ($row) => ($row->status instanceof DailyTaskStatus ? $row->status->value : $row->status) === $status->value
                )?->total ?? 0);
                $completed = $statusTotal(DailyTaskStatus::COMPLETED);
                $pending = $statusTotal(DailyTaskStatus::PENDING);
                $incomplete = $statusTotal(DailyTaskStatus::INCOMPLETE);
                $total = $completed + $pending + $incomplete;

                return [
                    'date' => $key,
                    'label' => $date->format('d/m'),
                    'completed' => $completed,
                    'pending' => $pending,
                    'incomplete' => $incomplete,
                    'total' => $total,
                    'completion_percent' => $total ? (int) round($completed / $total * 100) : 0,
                ];
            })
            ->values();

        $pointBalances = PointTransaction::query()
            ->selectRaw('user_id, sum(amount) as balance')
            ->groupBy('user_id')
            ->pluck('balance', 'user_id');

        $childPerformance = $children
            ->map(fn (User $child) => [
                'id' => $child->id,
                'name' => $child->name,
                'avatar' => $child->avatar,
                'points' => (int) ($pointBalances[$child->id] ?? 0),
                'completed_tasks' => $child->completed_tasks_count,
                'reward_redemptions' => $child->reward_redemptions_count,
            ])
            ->sortByDesc('completed_tasks')
            ->values();

        $categoryStats = Task::query()
            ->leftJoin('task_categories', 'tasks.category_id', '=', 'task_categories.id')
            ->leftJoin('daily_tasks', 'daily_tasks.task_id', '=', 'tasks.id')
            ->selectRaw('coalesce(task_categories.name, ?) as name, coalesce(task_categories.icon, ?) as icon, count(daily_tasks.id) as total, sum(case when daily_tasks.status = ? then 1 else 0 end) as completed', ['Không danh mục', '⭐', DailyTaskStatus::COMPLETED->value])
            ->groupBy('task_categories.id', 'task_categories.name', 'task_categories.icon')
            ->orderByDesc('completed')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'icon' => $row->icon,
                'total' => (int) $row->total,
                'completed' => (int) $row->completed,
                'completion_percent' => $row->total ? (int) round($row->completed / $row->total * 100) : 0,
            ]);

        $todayStatusRows = DailyTask::query()
            ->selectRaw('status, count(*) as total')
            ->whereDate('date', $today)
            ->groupBy('status')
            ->pluck('total', 'status');

        $topRewards = RewardRedemption::query()
            ->join('rewards', 'reward_redemptions.reward_id', '=', 'rewards.id')
            ->selectRaw('rewards.id, rewards.title, rewards.icon, count(*) as total, sum(points_spent) as points_spent')
            ->groupBy('rewards.id', 'rewards.title', 'rewards.icon')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'title' => $row->title,
                'icon' => $row->icon,
                'total' => (int) $row->total,
                'points_spent' => (int) $row->points_spent,
            ]);

        return [
            'children_count' => $children->count(),
            'active_tasks' => Task::query()->where('is_active', true)->count(),
            'completed_today' => (int) ($todayStatusRows[DailyTaskStatus::COMPLETED->value] ?? 0),
            'missed_today' => (int) ($todayStatusRows[DailyTaskStatus::PENDING->value] ?? 0),
            'incomplete_today' => (int) ($todayStatusRows[DailyTaskStatus::INCOMPLETE->value] ?? 0),
            'points_total' => (int) PointTransaction::query()->sum('amount'),
            'daily_trend' => $dailyTrend,
            'child_performance' => $childPerformance,
            'category_stats' => $categoryStats,
            'today_status' => [
                'completed' => (int) ($todayStatusRows[DailyTaskStatus::COMPLETED->value] ?? 0),
                'pending' => (int) ($todayStatusRows[DailyTaskStatus::PENDING->value] ?? 0),
                'incomplete' => (int) ($todayStatusRows[DailyTaskStatus::INCOMPLETE->value] ?? 0),
            ],
            'top_rewards' => $topRewards,
        ];
    }
}
