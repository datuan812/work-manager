<?php

namespace App\Http\Controllers\Api;

use App\Enums\DailyTaskStatus;
use App\Enums\UserRole;
use App\Actions\CompleteDailyTaskAction;
use App\Http\Controllers\Controller;
use App\Models\DailyTask;
use App\Models\DailyTaskDraft;
use App\Models\DailyTaskSubmission;
use App\Models\Reward;
use App\Models\User;
use App\Services\DailyTaskGenerator;
use App\Services\PointService;
use App\Services\RewardRedemptionService;
use App\Services\StreakService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        return $this->dailyTasksForDate($user, $generator, $points, $streaks, today('Asia/Ho_Chi_Minh')->toDateString());
    }

    public function dailyTasks(Request $request, User $user, DailyTaskGenerator $generator, PointService $points, StreakService $streaks)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return $this->dailyTasksForDate(
            $user,
            $generator,
            $points,
            $streaks,
            $validated['date'] ?? today('Asia/Ho_Chi_Minh')->toDateString(),
        );
    }

    public function submitDailyTasks(Request $request, User $user, DailyTaskGenerator $generator, CompleteDailyTaskAction $action, PointService $points, StreakService $streaks)
    {
        abort_unless($user->isChild() && $user->is_active, 404);

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'completed_task_ids' => ['array'],
            'completed_task_ids.*' => ['integer'],
        ]);

        $date = CarbonImmutable::parse($validated['date'], 'Asia/Ho_Chi_Minh')->startOfDay();
        $this->ensureViewableDate($date);
        $this->ensureEditableDate($user, $date);

        $generator->generateForChild($user, $date);

        $dailyTasks = DailyTask::query()
            ->with('task.category')
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->whereIn('status', [DailyTaskStatus::PENDING->value, DailyTaskStatus::COMPLETED->value, DailyTaskStatus::INCOMPLETE->value])
            ->orderBy('id')
            ->get();

        $allowedIds = $dailyTasks->pluck('id');
        $completedTaskIds = collect($validated['completed_task_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->intersect($allowedIds)
            ->values();

        $result = DB::transaction(function () use ($dailyTasks, $completedTaskIds, $action, $user, $date): array {
            $pointsAwarded = 0;

            foreach ($dailyTasks as $dailyTask) {
                if ($completedTaskIds->contains($dailyTask->id)) {
                    $taskResult = $action->complete($dailyTask);
                    $pointsAwarded += (int) ($taskResult['points_awarded'] ?? 0);

                    continue;
                }

                if ($dailyTask->status === DailyTaskStatus::COMPLETED) {
                    $taskResult = $action->uncomplete($dailyTask);
                    $pointsAwarded += (int) ($taskResult['points_awarded'] ?? 0);
                }

                $dailyTask->refresh()->update([
                    'status' => DailyTaskStatus::INCOMPLETE,
                    'completed_at' => null,
                ]);
            }

            DailyTaskSubmission::query()->firstOrCreate(
                ['user_id' => $user->id, 'date' => $date->toDateString()],
                ['submitted_at' => now()],
            );

            DailyTaskDraft::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $date)
                ->delete();

            return ['points_awarded' => $pointsAwarded];
        });

        return [
            ...$result,
            ...$this->dailyTasksForDate($user, $generator, $points, $streaks, $date->toDateString()),
        ];
    }

    public function saveDailyTaskDraft(Request $request, User $user, DailyTaskGenerator $generator, PointService $points, StreakService $streaks)
    {
        abort_unless($user->isChild() && $user->is_active, 404);

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'completed_task_ids' => ['array'],
            'completed_task_ids.*' => ['integer'],
        ]);

        $date = CarbonImmutable::parse($validated['date'], 'Asia/Ho_Chi_Minh')->startOfDay();
        $this->ensureViewableDate($date);
        $this->ensureEditableDate($user, $date);

        $generator->generateForChild($user, $date);

        $allowedIds = DailyTask::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->whereIn('status', [DailyTaskStatus::PENDING->value, DailyTaskStatus::COMPLETED->value, DailyTaskStatus::INCOMPLETE->value])
            ->pluck('id');

        $completedTaskIds = collect($validated['completed_task_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->intersect($allowedIds)
            ->values()
            ->all();

        DailyTaskDraft::query()->updateOrCreate(
            ['user_id' => $user->id, 'date' => $date->toDateString()],
            ['completed_task_ids' => $completedTaskIds],
        );

        return $this->dailyTasksForDate($user, $generator, $points, $streaks, $date->toDateString());
    }

    private function dailyTasksForDate(User $user, DailyTaskGenerator $generator, PointService $points, StreakService $streaks, string $dateValue): array
    {
        abort_unless($user->isChild() && $user->is_active, 404);

        $date = CarbonImmutable::parse($dateValue, 'Asia/Ho_Chi_Minh')->startOfDay();
        $this->ensureViewableDate($date);

        $generator->generateForChild($user, $date);

        $dailyTasks = DailyTask::query()
            ->with('task.category')
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->whereIn('status', [DailyTaskStatus::PENDING->value, DailyTaskStatus::COMPLETED->value, DailyTaskStatus::INCOMPLETE->value])
            ->orderBy('id')
            ->get();

        $completed = $dailyTasks->where('status', DailyTaskStatus::COMPLETED)->count();
        $submission = DailyTaskSubmission::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();
        $draft = DailyTaskDraft::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        return [
            'child' => $this->childPayload($user),
            'date' => $date->toDateString(),
            'day_status' => [
                'key' => $this->dayKey($date),
                'is_submitted' => $submission !== null,
                'submitted_at' => $submission?->submitted_at?->toIso8601String(),
                'can_edit' => $this->canEditDate($user, $date),
                'can_submit' => $this->canEditDate($user, $date),
            ],
            'draft_completed_task_ids' => $this->canEditDate($user, $date)
                ? ($draft?->completed_task_ids ?? [])
                : [],
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

    private function ensureViewableDate(CarbonImmutable $date): void
    {
        if (! in_array($this->dayKey($date), ['yesterday', 'today', 'tomorrow'], true)) {
            throw ValidationException::withMessages([
                'date' => 'Chỉ có thể xem nhiệm vụ hôm qua, hôm nay hoặc ngày mai.',
            ]);
        }
    }

    private function ensureEditableDate(User $user, CarbonImmutable $date): void
    {
        if (! $this->canEditDate($user, $date)) {
            throw ValidationException::withMessages([
                'date' => 'Ngày này chỉ có thể xem lại, không thể chốt hoặc chỉnh sửa.',
            ]);
        }
    }

    private function canEditDate(User $user, CarbonImmutable $date): bool
    {
        if (! in_array($this->dayKey($date), ['yesterday', 'today'], true)) {
            return false;
        }

        return ! DailyTaskSubmission::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->exists();
    }

    private function dayKey(CarbonImmutable $date): string
    {
        $today = today('Asia/Ho_Chi_Minh');

        return match (true) {
            $date->isSameDay($today->copy()->subDay()) => 'yesterday',
            $date->isSameDay($today) => 'today',
            $date->isSameDay($today->copy()->addDay()) => 'tomorrow',
            default => 'other',
        };
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

    public function rewardHistory(User $user)
    {
        abort_unless($user->isChild(), 404);

        return $user->rewardRedemptions()
            ->with('reward')
            ->latest('redeemed_at')
            ->limit(50)
            ->get()
            ->map(fn ($redemption) => [
                'id' => $redemption->id,
                'points_spent' => $redemption->points_spent,
                'redeemed_at' => $redemption->redeemed_at?->toIso8601String(),
                'reward' => $redemption->reward ? [
                    'id' => $redemption->reward->id,
                    'title' => $redemption->reward->title,
                    'description' => $redemption->reward->description,
                    'icon' => $redemption->reward->icon,
                ] : null,
            ]);
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
