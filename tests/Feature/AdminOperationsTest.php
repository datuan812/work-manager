<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Family;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_publishes_a_template_and_each_family_gets_its_own_editable_copy(): void
    {
        [$family, $parent] = $this->familyWithParent();
        $category = TaskCategory::create(['name' => 'Tự lập']);
        $payload = [
            'title' => 'Chuẩn bị cặp sách',
            'category_id' => $category->id,
            'points' => 12,
            'min_age' => 6,
            'max_age' => 9,
            'steps' => ['Xem thời khóa biểu', 'Cho sách vào cặp'],
            'is_active' => true,
        ];

        $this->actingAs($parent)->postJson('/api/admin/task-templates', $payload)->assertForbidden();
        $this->actingAs($this->admin());
        $templateId = $this->postJson('/api/admin/task-templates', $payload)->assertCreated()->json('id');
        $this->getJson('/api/admin/task-templates')->assertOk()->assertJsonPath('data.0.title', $payload['title']);

        $this->actingAs($parent)->withSession(['parent_access_granted' => true]);
        $this->getJson('/api/parent/task-templates?search=Chuẩn')->assertOk()->assertJsonPath('data.0.imported_task_id', null);
        $url = "/api/parent/task-templates/{$templateId}/import";
        $taskId = $this->postJson($url)->assertCreated()
            ->assertJsonPath('family_id', $family->id)->assertJsonPath('steps', $payload['steps'])->json('id');
        $this->postJson($url)->assertOk()->assertJsonPath('id', $taskId);
        $this->getJson('/api/parent/task-templates')->assertOk()->assertJsonPath('data.0.imported_task_id', $taskId);
        $child = User::create(['family_id' => $family->id, 'name' => 'Bé', 'role' => UserRole::CHILD]);
        $tomorrow = today('Asia/Ho_Chi_Minh')->addDay()->toDateString();
        $assignment = ['user_ids' => [$child->id], 'task_ids' => [$taskId]];
        $this->postJson('/api/parent/task-calendar', [...$assignment, 'dates' => [$tomorrow]])->assertCreated();
        $this->getJson("/api/children/{$child->id}/daily-tasks?date={$tomorrow}")->assertOk()
            ->assertJsonPath('tasks.0.task.steps', $payload['steps']);
        $this->putJson("/api/parent/tasks/{$taskId}", ['steps' => ['Dùng cặp mới']])->assertOk();
        $nextDay = today('Asia/Ho_Chi_Minh')->addDays(2)->toDateString();
        $this->postJson('/api/parent/task-calendar', [...$assignment, 'dates' => [$tomorrow, $nextDay]])->assertCreated();
        $this->getJson("/api/children/{$child->id}/daily-tasks?date={$tomorrow}")->assertOk()
            ->assertJsonCount(1, 'tasks')->assertJsonPath('tasks.0.task.steps', ['Dùng cặp mới']);
        $this->assertDatabaseHas('daily_tasks', [
            'task_id' => Task::query()->where('template_task_id', $taskId)->latest('id')->firstOrFail()->id,
            'date' => $nextDay,
        ]);
        $this->assertSame(['Dùng cặp mới'], Task::query()->where('template_task_id', $taskId)->latest('id')->firstOrFail()->steps);

        [$otherFamily, $otherParent] = $this->familyWithParent();
        $this->actingAs($otherParent)->withSession(['parent_access_granted' => true, 'family_id' => $otherFamily->id]);
        $this->putJson("/api/parent/tasks/{$taskId}", ['title' => 'Không được sửa'])->assertNotFound();
        $this->postJson($url)->assertCreated()->assertJsonPath('steps', $payload['steps']);

        $this->actingAs($this->admin())->withSession(['family_id' => null]);
        $this->getJson('/api/admin/dashboard')->assertOk()->assertJsonPath('popular_templates.0.families_count', 2);
        $this->deleteJson("/api/admin/task-templates/{$templateId}")->assertNoContent();
        $this->assertDatabaseHas('tasks', ['id' => $taskId, 'shared_task_template_id' => null]);
    }

    public function test_rotating_a_family_code_revokes_parent_and_child_sessions_and_is_audited(): void
    {
        [$family, $parent] = $this->familyWithParent();
        $child = User::create(['name' => 'Bé', 'role' => UserRole::CHILD, 'family_id' => $family->id, 'child_pin' => '1234']);
        $admin = $this->admin();
        $url = "/api/admin/families/{$family->id}/rotate-code";

        $this->actingAs($parent)->postJson($url)->assertForbidden();
        $newCode = $this->actingAs($admin)->postJson($url)->assertOk()->json('code');
        $this->assertNotSame($family->code, $newCode);
        $this->getJson('/api/admin/audit-logs?action=family.code_rotated')->assertOk()
            ->assertJsonPath('data.0.actor_id', $admin->id)->assertJsonPath('data.0.subject_id', $family->id)
            ->assertDontSee($newCode);

        $this->actingAs($parent)->withSession(['family_id' => $family->id, 'family_access_version' => 0]);
        $this->getJson('/api/parent/access-pin')->assertUnauthorized();
        $this->withSession(['family_id' => $family->id, 'family_access_version' => 0, "child_access.{$child->id}" => true]);
        $this->getJson("/api/children/{$child->id}/today")->assertUnauthorized();

        $this->postJson('/api/auth/family-login', ['family_code' => $family->code])->assertUnprocessable();
        $this->postJson('/api/auth/family-login', ['family_code' => $newCode])->assertOk();
        $this->getJson('/api/parent/access-pin')->assertOk();
        $this->getJson("/api/children/{$child->id}/today")->assertForbidden();

        $this->actingAs($admin)->postJson("/api/admin/families/{$family->id}/revoke-sessions")->assertNoContent();
        $this->actingAs($parent)->getJson('/api/auth/me')->assertUnauthorized();
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'family.sessions_revoked', 'subject_id' => $family->id]);
    }

    public function test_parent_pin_reset_requires_verification_and_uses_an_expiring_one_time_link(): void
    {
        [$family, $parent] = $this->familyWithParent();
        [, $otherParent] = $this->familyWithParent();
        $this->actingAs($parent)->putJson('/api/parent/access-pin', ['pin' => '9876'])->assertUnprocessable();
        $this->patchJson('/api/parent/access-pin/toggle', ['enabled' => false])->assertUnprocessable();

        $this->actingAs($this->admin())->withSession(['family_id' => null]);
        $url = "/api/admin/families/{$family->id}/pin-resets";
        $this->postJson($url, ['user_id' => $parent->id, 'identity_verified' => false])->assertUnprocessable();
        $this->postJson($url, ['user_id' => $otherParent->id, 'identity_verified' => true])->assertNotFound();
        $secret = $this->postJson($url, ['user_id' => $parent->id, 'identity_verified' => true])->assertCreated()->json('token');
        $this->assertDatabaseHas('parent_pin_resets', ['user_id' => $parent->id, 'token_hash' => hash('sha256', $secret)]);
        $this->getJson('/api/admin/audit-logs?action=parent.pin_reset_issued')->assertOk()->assertDontSee($secret);
        $this->assertTrue(Hash::check('2468', $parent->fresh()->parent_access_pin));
        $this->postJson('/api/auth/logout')->assertOk();

        $payload = ['token' => $secret, 'pin' => '098765', 'pin_confirmation' => '098765'];
        $this->travel(31)->minutes();
        $this->postJson('/api/auth/reset-parent-pin', $payload)->assertUnprocessable();
        $this->travelBack();
        $this->postJson('/api/auth/reset-parent-pin', $payload)->assertNoContent();
        $this->postJson('/api/auth/reset-parent-pin', $payload)->assertUnprocessable();
        $this->assertTrue(Hash::check('098765', $parent->fresh()->parent_access_pin));
        $this->assertDatabaseMissing('parent_pin_resets', ['user_id' => $parent->id]);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'parent.pin_reset_completed', 'subject_id' => $parent->id]);
    }

    public function test_dashboard_counts_weekly_family_activity_and_admin_role_and_lock_changes(): void
    {
        [$family, $parent] = $this->familyWithParent();
        $child = User::create(['name' => 'Bé', 'role' => UserRole::CHILD, 'family_id' => $family->id]);
        $task = Task::create(['family_id' => $family->id, 'user_id' => $child->id, 'title' => 'Đọc sách']);
        $task->dailyTasks()->create(['family_id' => $family->id, 'user_id' => $child->id, 'date' => today()])->delete();
        $lastWeek = today('Asia/Ho_Chi_Minh')->startOfWeek()->subWeek()->toDateString();
        DB::table('family_weekly_activity')->insert(['family_id' => $family->id, 'week_start' => $lastWeek]);
        $this->postJson('/api/auth/family-login', ['family_code' => $family->code])->assertOk();
        $this->getJson('/api/children')->assertOk();
        $this->getJson('/api/children')->assertOk();

        $this->actingAs($this->admin())->withSession(['family_id' => null]);
        $this->getJson('/api/admin/dashboard')->assertOk()->assertJsonPath('families_with_tasks', 1)
            ->assertJsonPath('active_this_week', 1)->assertJsonPath('returned_this_week', 1);
        $this->patchJson("/api/admin/users/{$parent->id}", ['is_active' => false, 'role' => 'child'])->assertOk();
        $this->getJson('/api/admin/audit-logs?action=user.locked')->assertOk()->assertJsonPath('data.0.subject_id', $parent->id);
        $this->getJson('/api/admin/audit-logs?action=user.role_changed')->assertOk()->assertJsonPath('data.0.details.before', 'parent');
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'role' => UserRole::ADMIN]);
    }

    private function familyWithParent(): array
    {
        $family = Family::create(['name' => 'Gia đình', 'code' => strtoupper(Str::random(16))]);
        $parent = User::create([
            'family_id' => $family->id,
            'name' => 'Phụ huynh',
            'role' => UserRole::PARENT,
            'parent_access_pin' => '2468',
        ]);

        return [$family, $parent];
    }
}
