<?php

namespace App\Http\Controllers\Api\Parent;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\RewardRedemption;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RewardHistoryController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'user_id' => ['nullable', Rule::exists('users', 'id')->where('role', UserRole::CHILD->value)],
            'limit' => ['nullable', 'integer', 'min:10', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $endDate = Carbon::parse($data['end_date'] ?? today('Asia/Ho_Chi_Minh'))->endOfDay();
        $startDate = Carbon::parse($data['start_date'] ?? $endDate->copy()->subDays(30))->startOfDay();

        if ($startDate->diffInDays($endDate) > 180) {
            throw ValidationException::withMessages([
                'end_date' => 'Khoảng thời gian xem lịch sử không được vượt quá 180 ngày.',
            ]);
        }

        $baseQuery = RewardRedemption::query()
            ->with(['reward', 'user:id,name,role,avatar'])
            ->whereBetween('redeemed_at', [$startDate, $endDate])
            ->when($data['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId));

        $perPage = (int) ($data['per_page'] ?? $data['limit'] ?? 25);
        $paginator = (clone $baseQuery)
            ->latest('redeemed_at')
            ->latest('id')
            ->paginate($perPage);

        $items = collect($paginator->items())->map(fn (RewardRedemption $redemption) => [
                'id' => $redemption->id,
                'points_spent' => $redemption->points_spent,
                'redeemed_at' => $redemption->redeemed_at?->toIso8601String(),
                'reward' => $redemption->reward ? [
                    'id' => $redemption->reward->id,
                    'title' => $redemption->reward->title,
                    'description' => $redemption->reward->description,
                    'icon' => $redemption->reward->icon,
                ] : null,
                'user' => $redemption->user ? [
                    'id' => $redemption->user->id,
                    'name' => $redemption->user->name,
                    'avatar' => $redemption->user->avatar,
                ] : null,
            ]);

        return [
            'items' => $items,
            'summary' => [
                'total' => (clone $baseQuery)->count(),
                'points_spent' => (int) (clone $baseQuery)->sum('points_spent'),
                'children' => (clone $baseQuery)->distinct('user_id')->count('user_id'),
            ],
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }
}
