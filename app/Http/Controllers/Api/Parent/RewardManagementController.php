<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use Illuminate\Http\Request;

class RewardManagementController extends Controller
{
    public function index()
    {
        return Reward::query()->orderBy('required_points')->get();
    }

    public function store(Request $request)
    {
        return response()->json(Reward::create($request->validate($this->rules())), 201);
    }

    public function update(Request $request, Reward $reward)
    {
        $reward->update($request->validate($this->rules(false)));

        return $reward;
    }

    public function destroy(Reward $reward)
    {
        $reward->delete();

        return response()->noContent();
    }

    private function rules(bool $creating = true): array
    {
        return [
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:20'],
            'required_points' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
