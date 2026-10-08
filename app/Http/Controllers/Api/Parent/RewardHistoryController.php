<?php

namespace App\Http\Controllers\Api\Parent;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\RewardRedemption;
use App\Services\RewardRedemptionService;
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
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
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
            ->whereBetween('requested_at', [$startDate, $endDate])
            ->when($data['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status));

        $perPage = (int) ($data['per_page'] ?? $data['limit'] ?? 25);
        $paginator = (clone $baseQuery)
            ->latest('requested_at')
            ->latest('id')
            ->paginate($perPage);

        $items = collect($paginator->items())->map(fn (RewardRedemption $redemption) => [
                'id' => $redemption->id,
                'points_spent' => $redemption->points_spent,
                'status' => $redemption->status,
                'requested_at' => $redemption->requested_at?->toIso8601String(),
                'approved_at' => $redemption->approved_at?->toIso8601String(),
                'rejected_at' => $redemption->rejected_at?->toIso8601String(),
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
                'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
                'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
                'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
                'points_spent' => (int) (clone $baseQuery)->where('status', 'approved')->sum('points_spent'),
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

    public function approve(RewardRedemption $redemption, RewardRedemptionService $redemptions)
    {
        return ['redemption' => $redemptions->approve($redemption)];
    }

    public function reject(RewardRedemption $redemption, RewardRedemptionService $redemptions)
    {
        return ['redemption' => $redemptions->reject($redemption)];
    }
}
