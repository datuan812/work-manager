<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\TaskCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function storeFamily(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'alpha_num', 'min:4', 'max:32', 'unique:families,code'],
        ]);

        $code = strtoupper($data['code'] ?? 'GD'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)));
        while (Family::query()->where('code', $code)->exists()) {
            $code = 'GD'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        }

        $result = DB::transaction(function () use ($data, $code): array {
            $family = Family::create([
                'name' => $data['name'],
                'code' => $code,
                'is_active' => true,
            ]);
            $parent = User::create([
                'family_id' => $family->id,
                'name' => $data['parent_name'] ?? 'Phụ huynh',
                'email' => null,
                'password' => null,
                'role' => UserRole::PARENT,
                'is_active' => true,
            ]);

            return [$family, $parent];
        });

        return response()->json([
            'family' => $result[0]->loadCount('users'),
            'parent' => $result[1],
            'message' => 'Đã tạo family và tài khoản parent mặc định.',
        ], 201);
    }

    public function users(Request $request)
    {
        return User::withoutGlobalScopes()->with('family:id,name,code')->latest()->paginate(25);
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'role' => ['sometimes', Rule::enum(UserRole::class)],
            'is_active' => ['sometimes', 'boolean'],
            'family_id' => ['nullable', 'exists:families,id'],
        ]);
        $user->update($data);

        return $user->load('family:id,name,code');
    }

    public function families()
    {
        return Family::withCount('users')->latest()->get();
    }

    public function updateFamily(Request $request, Family $family)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'alpha_num', 'min:4', 'max:32', Rule::unique('families', 'code')->ignore($family)],
        ]);

        $family->update([
            'name' => $data['name'],
            'code' => strtoupper(trim($data['code'])),
        ]);

        return $family->loadCount('users');
    }

    public function destroyFamily(Family $family)
    {
        $deletedUsers = 0;

        DB::transaction(function () use ($family, &$deletedUsers): void {
            $userIds = $family->users()->withoutGlobalScopes()->pluck('id');
            $deletedUsers = $userIds->count();

            DB::table('daily_task_submissions')->where('family_id', $family->id)->delete();
            DB::table('daily_task_drafts')->where('family_id', $family->id)->delete();
            DB::table('point_transactions')->where('family_id', $family->id)->delete();
            DB::table('reward_redemptions')->where('family_id', $family->id)->delete();
            DB::table('daily_tasks')->where('family_id', $family->id)->delete();
            DB::table('tasks')->where('family_id', $family->id)->delete();
            DB::table('rewards')->where('family_id', $family->id)->delete();
            DB::table('task_categories')->where('family_id', $family->id)->delete();
            DB::table('users')->whereIn('id', $userIds)->delete();
            $family->delete();
        });

        return ['message' => 'Đã xóa family và dữ liệu người dùng liên quan.', 'deleted_users' => $deletedUsers];
    }

    public function categories()
    {
        return TaskCategory::query()
            ->whereNull('family_id')
            ->orderBy('name')
            ->get();
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate($this->categoryRules());
        $category = TaskCategory::create($data);

        return response()->json($category, 201);
    }

    public function updateCategory(Request $request, int $category)
    {
        $category = TaskCategory::query()->whereNull('family_id')->findOrFail($category);
        $data = $request->validate($this->categoryRules($category->id));
        $category->update($data);

        return $category->refresh();
    }

    public function destroyCategory(int $category)
    {
        $category = TaskCategory::query()->whereNull('family_id')->findOrFail($category);
        $category->delete();

        return response()->noContent();
    }

    private function categoryRules(?int $ignoreId = null): array
    {
        $nameRule = Rule::unique('task_categories', 'name')->whereNull('family_id');

        if ($ignoreId !== null) {
            $nameRule->ignore($ignoreId);
        }

        return [
            'name' => ['required', 'string', 'max:100', $nameRule],
            'icon' => ['nullable', 'string', 'max:20'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
