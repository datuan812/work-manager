<?php

namespace App\Http\Middleware;

use App\Models\DailyTask;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureChildAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $child = $this->childFromRoute($request);

        abort_unless($child && (session()->get('family_id') === null || session()->get('family_id') === $child->family_id), 404);

        $user = $request->user();
        if ($user?->isParent()) {
            abort_unless($user->isInFamily($child), 404);
        }

        abort_unless($child?->isChild() && $child->is_active, 404);
        $parentHasAccess = $user?->isParent() && (
            ! $user->parent_access_pin_enabled || $request->session()->get('parent_access_granted') === true
        );
        abort_unless($parentHasAccess || $request->session()->get($this->sessionKey($child)), 403);

        return $next($request);
    }

    private function childFromRoute(Request $request): ?User
    {
        $user = $request->route('user');

        if ($user instanceof User) {
            return $user;
        }

        $dailyTask = $request->route('dailyTask');

        if ($dailyTask instanceof DailyTask) {
            return $dailyTask->user;
        }

        return null;
    }

    private function sessionKey(User $child): string
    {
        return "child_access.{$child->id}";
    }
}
