<?php

namespace App\Http\Middleware;

use App\Models\Family;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureFamilySession
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        $user = $request->user();
        $familyId = $session->get('family_id');

        if ($user?->isParent()) {
            abort_unless($user->is_active && $user->family_id !== null, 403);
            $familyId = $user->family_id;
        }

        if ($familyId === null) {
            return $next($request);
        }

        $family = Family::query()->find($familyId);
        $versionMatches = $family && $family->access_version === (int) $session->get('family_access_version', 0);

        if (! $family?->is_active || ! $versionMatches || Auth::viaRemember()) {
            // Forget this browser's authentication without rotating the family's current token.
            Auth::logoutCurrentDevice();
            $session->invalidate();
            $session->regenerateToken();
            abort(401, 'Phiên gia đình đã hết hiệu lực. Vui lòng đăng nhập lại.');
        }

        $session->put('family_id', $family->id);
        $response = $next($request);
        $week = today('Asia/Ho_Chi_Minh')->startOfWeek(CarbonInterface::MONDAY)->toDateString();
        $activityKey = $family->id.':'.$week;

        if ($response->isSuccessful() && $session->get('family_activity_week') !== $activityKey) {
            DB::table('family_weekly_activity')->insertOrIgnore(['family_id' => $family->id, 'week_start' => $week]);
            $session->put('family_activity_week', $activityKey);
        }

        return $response;
    }
}
