<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AchievementController extends Controller
{
    public function index()
    {
        return Achievement::query()->withCount('users')->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        $achievement = Achievement::create($this->validated($request));

        return response()->json($achievement->loadCount('users'), 201);
    }

    public function update(Request $request, Achievement $achievement)
    {
        $achievement->update($this->validated($request, $achievement));

        return $achievement->loadCount('users');
    }

    public function destroy(Achievement $achievement)
    {
        $achievement->delete();

        return response()->noContent();
    }

    private function validated(Request $request, ?Achievement $achievement = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('achievements', 'code')->ignore($achievement),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:20'],
            'criteria' => [$achievement ? 'sometimes' : 'required', 'array'],
            'criteria.type' => ['required_with:criteria', Rule::in(['completed_tasks', 'streak_days', 'perfect_day'])],
            'criteria.value' => ['nullable', 'integer', 'min:1', 'required_unless:criteria.type,perfect_day'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
