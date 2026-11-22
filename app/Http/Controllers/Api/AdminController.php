<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Family;
use App\Models\TaskCategory;
use App\Models\User;
use App\Services\FamilySessionService;
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

        $code = strtoupper($data['code'] ?? 'GD'.bin2hex(random_bytes(10)));
        while (Family::query()->where('code', $code)->exists()) {
            $code = 'GD'.strtoupper(bin2hex(random_bytes(10)));
        }

        $result = DB::transaction(function () use ($request, $data, $code): array {
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

            AdminAuditLog::record($request->user(), 'family.created', $family);

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
        DB::transaction(function () use ($request, $user, $data): void {
            $user = User::withoutGlobalScope('family')->lockForUpdate()->findOrFail($user->id);
            $before = $user->only(['role', 'is_active', 'family_id']);
            $user->fill($data);

            foreach (['role', 'is_active', 'family_id'] as $field) {
                if ($user->isDirty($field)) {
                    $user->remember_token = null;
                    DB::table('parent_pin_resets')->where('user_id', $user->id)->delete();
                    break;
                }
            }

            $user->save();
            $actions = [
                'role' => 'user.role_changed',
                'is_active' => $user->is_active ? 'user.unlocked' : 'user.locked',
                'family_id' => 'user.family_changed',
                'name' => 'user.updated',
            ];
            foreach ($actions as $field => $action) {
                if ($user->wasChanged($field)) {
                    AdminAuditLog::record($request->user(), $action, $user, $field === 'name' ? [] : [
                        'field' => $field,
                        'before' => $before[$field],
                        'after' => $user->{$field},
                    ]);
                }
            }
        });

        return $user->refresh()->load('family:id,name,code');
    }

    public function families()
    {
        return Family::withCount('users')->latest()->get();
    }

    public function updateFamily(Request $request, Family $family, FamilySessionService $sessions)
    {
        if (is_string($request->input('code'))) {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'alpha_num', 'min:4', 'max:32', Rule::unique('families', 'code')->ignore($family)],
        ]);

        return DB::transaction(function () use ($request, $family, $data, $sessions) {
            $family = Family::query()->lockForUpdate()->findOrFail($family->id);
            $family->update($data);
            $changes = $family->getChanges();
            if (array_key_exists('code', $changes)) {
                $sessions->revoke($family);
                AdminAuditLog::record($request->user(), 'family.code_rotated', $family);
            }
            if (array_key_exists('name', $changes)) {
                AdminAuditLog::record($request->user(), 'family.updated', $family);
            }

            return $family->loadCount('users');
        });
    }

    public function destroyFamily(Request $request, Family $family)
    {
        $deletedUsers = 0;

        DB::transaction(function () use ($request, $family, &$deletedUsers): void {
            $family = Family::query()->lockForUpdate()->findOrFail($family->id);
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
            AdminAuditLog::record($request->user(), 'family.deleted', $family, ['deleted_users' => $deletedUsers]);
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
        $category = DB::transaction(function () use ($request, $data) {
            $category = TaskCategory::create($data);
            AdminAuditLog::record($request->user(), 'category.created', $category);

            return $category;
        });

        return response()->json($category, 201);
    }

    public function updateCategory(Request $request, int $category)
    {
        $category = TaskCategory::query()->whereNull('family_id')->findOrFail($category);
        $data = $request->validate($this->categoryRules($category->id));
        DB::transaction(function () use ($request, $category, $data) {
            $category->update($data);
            AdminAuditLog::record($request->user(), 'category.updated', $category);
        });

        return $category->refresh();
    }

    public function destroyCategory(Request $request, int $category)
    {
        $category = TaskCategory::query()->whereNull('family_id')->findOrFail($category);
        DB::transaction(function () use ($request, $category) {
            AdminAuditLog::record($request->user(), 'category.deleted', $category);
            $category->delete();
        });

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
