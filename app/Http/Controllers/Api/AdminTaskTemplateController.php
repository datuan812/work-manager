<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\SharedTaskTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminTaskTemplateController extends Controller
{
    public function index()
    {
        return SharedTaskTemplate::query()->with('category:id,name,icon')->withCount('families')->latest('id')->paginate(24);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        return DB::transaction(function () use ($request, $data) {
            $template = SharedTaskTemplate::create($data);
            AdminAuditLog::record($request->user(), 'template.created', $template);

            return response()->json($template, 201);
        });
    }

    public function update(Request $request, SharedTaskTemplate $taskTemplate)
    {
        $data = $this->validated($request);

        return DB::transaction(function () use ($request, $taskTemplate, $data) {
            $taskTemplate->update($data);
            AdminAuditLog::record($request->user(), 'template.updated', $taskTemplate);

            return $taskTemplate;
        });
    }

    public function destroy(Request $request, SharedTaskTemplate $taskTemplate)
    {
        DB::transaction(function () use ($request, $taskTemplate) {
            AdminAuditLog::record($request->user(), 'template.deleted', $taskTemplate);
            $taskTemplate->delete();
        });

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', Rule::exists('task_categories', 'id')->whereNull('family_id')],
            'icon' => ['nullable', 'string', 'max:20'],
            'points' => ['required', 'integer', 'between:1,500'],
            'min_age' => ['nullable', 'integer', 'between:0,18'],
            'max_age' => ['nullable', 'integer', 'between:0,18', ...($request->filled('min_age') ? ['gte:min_age'] : [])],
            'steps' => ['nullable', 'array', 'list', 'max:12'],
            'steps.*' => ['required', 'string', 'max:200'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
