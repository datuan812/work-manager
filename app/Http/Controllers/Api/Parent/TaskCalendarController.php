<?php

namespace App\Http\Controllers\Api\Parent;

use App\Enums\DailyTaskStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\DailyTask;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaskCalendarController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'user_id' => ['nullable', Rule::exists('users', 'id')->where('role', UserRole::CHILD->value)],
        ]);

        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = Carbon::parse($data['end_date'])->startOfDay();

        if ($startDate->diffInDays($endDate) > 93) {
            throw ValidationException::withMessages([
                'end_date' => 'Khoảng thời gian xem lịch không được vượt quá 93 ngày.',
            ]);
        }

        $assignments = DailyTask::query()
            ->with(['task.category', 'user:id,name,role,avatar'])
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($data['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->orderBy('date')
            ->latest('id')
            ->get();

        return [
            'assignments' => $assignments,
            'by_date' => $assignments->groupBy(fn (DailyTask $dailyTask) => $dailyTask->date->toDateString()),
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['nullable', Rule::exists('users', 'id')->where('role', UserRole::CHILD->value)],
            'user_ids' => ['nullable', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('role', UserRole::CHILD->value)],
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'distinct', Rule::exists('tasks', 'id')],
            'dates' => ['required', 'array', 'min:1', 'max:93'],
            'dates.*' => ['date', 'distinct'],
        ]);

        $userIds = collect($data['user_ids'] ?? [$data['user_id'] ?? null])->filter()->unique()->values();

        if ($userIds->isEmpty()) {
            throw ValidationException::withMessages([
                'user_ids' => 'Vui lòng chọn ít nhất một bé nhận nhiệm vụ.',
            ]);
        }

        $lockedDates = collect($data['dates'])
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->filter(fn ($date) => Carbon::parse($date)->lte(today('Asia/Ho_Chi_Minh')));

        if ($lockedDates->isNotEmpty()) {
            throw ValidationException::withMessages([
                'dates' => 'Không thể thay đổi nhiệm vụ trong ngày đã đến hạn hoặc đã qua.',
            ]);
        }

        $tasks = Task::query()
            ->where('is_active', true)
            ->whereIn('id', $data['task_ids'])
            ->get();

        if ($tasks->count() !== count($data['task_ids'])) {
            throw ValidationException::withMessages([
                'task_ids' => 'Một số nhiệm vụ không tồn tại hoặc đang bị tắt.',
            ]);
        }

        foreach ($userIds as $userId) {
            $userId = (int) $userId;

            foreach ($data['dates'] as $date) {
                $date = Carbon::parse($date)->toDateString();

                foreach ($tasks as $task) {
                    $childTask = (int) $task->user_id === $userId ? $task : $this->taskForChild($task, $userId);

                    DailyTask::query()->firstOrCreate(
                        ['task_id' => $childTask->id, 'date' => $date],
                        ['user_id' => $childTask->user_id, 'status' => DailyTaskStatus::PENDING->value],
                    );
                }
            }
        }

        return response()->json(['message' => 'Đã giao nhiệm vụ.'], 201);
    }

    public function destroy(DailyTask $dailyTask)
    {
        abort_unless($dailyTask->user->isChild(), 404);

        if ($dailyTask->date->lte(today('Asia/Ho_Chi_Minh'))) {
            throw ValidationException::withMessages([
                'daily_task' => 'Không thể thay đổi nhiệm vụ trong ngày đã đến hạn hoặc đã qua.',
            ]);
        }

        if ($dailyTask->status === DailyTaskStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'daily_task' => 'Không thể xóa nhiệm vụ đã hoàn thành.',
            ]);
        }

        $dailyTask->delete();

        return response()->noContent();
    }

    private function taskForChild(Task $template, int $userId): Task
    {
        return Task::query()->firstOrCreate(
            [
                'user_id' => $userId,
                'title' => $template->title,
                'category_id' => $template->category_id,
                'points' => $template->points,
                'is_active' => true,
            ],
            [
                'description' => $template->description,
                'icon' => $template->icon,
            ],
        );
    }
}
