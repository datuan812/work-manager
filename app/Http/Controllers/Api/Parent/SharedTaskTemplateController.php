<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\SharedTaskTemplate;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SharedTaskTemplateController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $importedTask = Task::query()->where('family_id', $request->user()->family_id)
            ->whereColumn('tasks.shared_task_template_id', 'shared_task_templates.id')->select('id')->limit(1);

        return SharedTaskTemplate::query()
            ->with('category:id,name,icon')
            ->where('is_active', true)
            ->whereNotNull('category_id')
            ->when($data['search'] ?? null, fn ($query, $value) => $query->whereLike('title', '%'.$value.'%'))
            ->select('shared_task_templates.*')
            ->selectSub($importedTask, 'imported_task_id')
            ->orderBy('title')
            ->paginate(12);
    }

    public function import(Request $request, SharedTaskTemplate $taskTemplate)
    {
        $familyId = $request->user()->family_id;

        $task = DB::transaction(function () use ($familyId, $taskTemplate) {
            $template = SharedTaskTemplate::query()
                ->where('is_active', true)
                ->whereNotNull('category_id')
                ->lockForUpdate()
                ->findOrFail($taskTemplate->id);

            $task = Task::query()->firstOrCreate(
                ['family_id' => $familyId, 'shared_task_template_id' => $template->id],
                $template->only(['category_id', 'title', 'description', 'icon', 'points', 'steps']),
            );

            DB::table('shared_task_template_imports')->insertOrIgnore([
                'family_id' => $familyId,
                'shared_task_template_id' => $template->id,
            ]);

            return $task->load('category');
        });

        return response()->json($task, $task->wasRecentlyCreated ? 201 : 200);
    }
}
