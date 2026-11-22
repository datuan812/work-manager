<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Family;
use App\Models\SharedTaskTemplate;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOperationsController extends Controller
{
    public function dashboard()
    {
        $week = today('Asia/Ho_Chi_Minh')->startOfWeek(CarbonInterface::MONDAY);
        $current = $week->toDateString();
        $previous = $week->copy()->subWeek()->toDateString();
        $previouslyActive = DB::table('family_weekly_activity')->where('week_start', $previous);

        return [
            'families_total' => Family::count(),
            'families_with_tasks' => Family::query()->whereNotNull('first_task_assigned_at')->count(),
            'active_this_week' => DB::table('family_weekly_activity')->where('week_start', $current)->count(),
            'active_previous_week' => (clone $previouslyActive)->count(),
            'returned_this_week' => (clone $previouslyActive)->whereIn('family_id', DB::table('family_weekly_activity')
                ->where('week_start', $current)->select('family_id'))->count(),
            'week_start' => $current,
            'previous_week_start' => $previous,
            'popular_templates' => SharedTaskTemplate::query()->select('id', 'title')
                ->withCount('families')->has('families')->orderByDesc('families_count')->orderBy('id')->limit(8)->get(),
        ];
    }

    public function auditLogs(Request $request)
    {
        $data = $request->validate(['action' => ['nullable', 'string', 'max:64']]);

        return AdminAuditLog::query()
            ->when($data['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->latest('id')
            ->paginate(25);
    }
}
