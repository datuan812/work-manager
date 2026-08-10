<?php

namespace App\Http\Controllers\Api\Parent;

use App\Enums\RepeatType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskManagementController extends Controller
{
    public function index()
    {
        return [
            'tasks' => Task::query()->with(['user:id,name,role,avatar', 'category', 'schedule'])->latest()->get(),
            'categories' => TaskCategory::query()->orderBy('sort_order')->get(),
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $schedule = $data['schedule'];
        unset($data['schedule']);

        $task = Task::create($data);
        $task->schedule()->create($schedule);

        return response()->json($task->load(['user:id,name,role,avatar', 'category', 'schedule']), 201);
    }

    public function update(Request $request, Task $task)
    {
        abort_unless($task->user->isChild(), 404);

        $data = $request->validate($this->rules(false));
        $schedule = $data['schedule'] ?? null;
        unset($data['schedule']);

        $task->update($data);

        if ($schedule) {
            $task->schedule()->updateOrCreate(['task_id' => $task->id], $schedule);
        }

        return $task->load(['user:id,name,role,avatar', 'category', 'schedule']);
    }

    public function destroy(Task $task)
    {
        abort_unless($task->user->isChild(), 404);
        $task->delete();

        return response()->noContent();
    }

    private function rules(bool $creating = true): array
    {
        return [
            'user_id' => [$creating ? 'required' : 'sometimes', Rule::exists('users', 'id')->where('role', UserRole::CHILD->value)],
            'category_id' => ['nullable', Rule::exists('task_categories', 'id')],
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:20'],
            'points' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'schedule' => [$creating ? 'required' : 'sometimes', 'array'],
            'schedule.repeat_type' => [$creating ? 'required' : 'sometimes', Rule::enum(RepeatType::class)],
            'schedule.start_date' => [$creating ? 'required' : 'sometimes', 'date'],
            'schedule.end_date' => ['nullable', 'date', 'after_or_equal:schedule.start_date'],
            'schedule.days_of_week' => ['nullable', 'array'],
            'schedule.days_of_week.*' => ['integer', 'between:1,7'],
            'schedule.time_of_day' => ['nullable', 'date_format:H:i'],
        ];
    }
}
