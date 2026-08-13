<?php

namespace App\Http\Controllers\Api\Parent;

use App\Enums\DailyTaskStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\DailyTask;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaskHistoryController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'user_id' => ['nullable', Rule::exists('users', 'id')->where('role', UserRole::CHILD->value)],
            'status' => ['nullable', Rule::enum(DailyTaskStatus::class)],
            'limit' => ['nullable', 'integer', 'min:10', 'max:200'],
        ]);

        $endDate = Carbon::parse($data['end_date'] ?? today('Asia/Ho_Chi_Minh'))->endOfDay();
        $startDate = Carbon::parse($data['start_date'] ?? $endDate->copy()->subDays(30))->startOfDay();

        if ($startDate->diffInDays($endDate) > 180) {
            throw ValidationException::withMessages([
                'end_date' => 'Khoảng thời gian xem lịch sử không được vượt quá 180 ngày.',
            ]);
        }

        $baseQuery = DailyTask::query()
            ->with(['task.category', 'user:id,name,role,avatar'])
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($data['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status));

        $items = (clone $baseQuery)
            ->latest('date')
            ->latest('id')
            ->limit($data['limit'] ?? 100)
            ->get();

        $summary = (clone $baseQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'items' => $items,
            'summary' => [
                'total' => $summary->sum(),
                'completed' => (int) ($summary[DailyTaskStatus::COMPLETED->value] ?? 0),
                'pending' => (int) ($summary[DailyTaskStatus::PENDING->value] ?? 0),
                'skipped' => (int) ($summary[DailyTaskStatus::SKIPPED->value] ?? 0),
            ],
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ];
    }
}
