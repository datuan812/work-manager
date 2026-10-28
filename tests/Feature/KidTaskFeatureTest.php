<?php

namespace Tests\Feature;

use App\Actions\CompleteDailyTaskAction;
use App\Enums\DailyTaskStatus;
use App\Enums\RepeatType;
use App\Enums\UserRole;
use App\Models\DailyTask;
use App\Models\Reward;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use App\Services\DailyTaskGenerator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class KidTaskFeatureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_parent_can_login_and_logout(): void
    {
        User::create([
            'name' => 'Parent',
            'email' => 'login-parent@example.com',
            'password' => Hash::make('12345678'),
            'role' => UserRole::PARENT,
        ]);

        $this->postJson('/api/auth/login', ['email' => 'login-parent@example.com', 'password' => '12345678'])
            ->assertOk()
            ->assertJsonPath('user.role', 'parent');

        $this->postJson('/api/auth/logout')->assertOk();
    }

    public function test_parent_can_create_update_and_delete_child(): void
    {
        $parent = $this->parent();
        Storage::fake('public');

        $childId = $this->actingAs($parent)->post('/api/parent/children', [
            'name' => 'Minh',
            'access_pin' => '1234',
            'avatar_file' => UploadedFile::fake()->image('minh.jpg'),
            'date_of_birth' => '2018-01-01',
        ])->assertCreated()->assertJsonPath('avatar', fn ($avatar) => str_starts_with($avatar, '/storage/avatars/'))->json('id');

        $this->assertDatabaseHas('users', ['id' => $childId, 'role' => 'child', 'email' => null]);

        $this->actingAs($parent)->putJson("/api/parent/children/{$childId}", ['name' => 'Minh A'])
            ->assertOk()
            ->assertJsonPath('name', 'Minh A');

        $this->actingAs($parent)->deleteJson("/api/parent/children/{$childId}")->assertNoContent();
    }

    public function test_parent_cannot_create_task_for_parent_user(): void
    {
        $parent = $this->parent();
        $category = TaskCategory::create(['name' => 'Study']);

        $this->actingAs($parent)->postJson('/api/parent/tasks', [
            'user_id' => $parent->id,
            'category_id' => $category->id,
            'title' => 'Invalid',
            'points' => 10,
            'schedule' => ['repeat_type' => 'daily', 'start_date' => today()->toDateString()],
        ])->assertUnprocessable();
    }

    public function test_daily_task_generation_is_idempotent(): void
    {
        [$child, $task] = $this->scheduledTask();
        $generator = app(DailyTaskGenerator::class);

        $generator->generateForChild($child, today());
        $generator->generateForChild($child, today());

        $this->assertSame(1, DailyTask::where('task_id', $task->id)->whereDate('date', today())->count());
    }

    public function test_task_calendar_assigns_templates_to_child_task_copies(): void
    {
        $parent = $this->parent();
        $child = User::create(['name' => 'Minh', 'role' => UserRole::CHILD, 'child_pin' => Hash::make('1234')]);
        $category = TaskCategory::create(['name' => 'Study']);
        $template = Task::create([
            'user_id' => null,
            'category_id' => $category->id,
            'title' => 'Read',
            'icon' => '📖',
            'points' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($parent)->postJson('/api/parent/task-calendar', [
            'user_ids' => [$child->id],
            'task_ids' => [$template->id],
            'dates' => [today('Asia/Ho_Chi_Minh')->addDay()->toDateString()],
            'reminder_time' => '19:30',
        ])->assertCreated();

        $childTask = Task::query()
            ->where('user_id', $child->id)
            ->where('template_task_id', $template->id)
            ->first();

        $this->assertNotNull($childTask);
        $this->assertDatabaseHas('daily_tasks', [
            'task_id' => $childTask->id,
            'user_id' => $child->id,
            'date' => today('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
            'reminder_time' => '19:30:00',
        ]);
    }

    public function test_task_calendar_rejects_child_specific_tasks(): void
    {
        $parent = $this->parent();
        [$child, $childTask] = $this->scheduledTask();

        $this->actingAs($parent)->postJson('/api/parent/task-calendar', [
            'user_ids' => [$child->id],
            'task_ids' => [$childTask->id],
            'dates' => [today('Asia/Ho_Chi_Minh')->addDay()->toDateString()],
        ])->assertUnprocessable();
    }

    public function test_child_area_requires_pin_unlock(): void
    {
        [$child] = $this->scheduledTask();

        $this->getJson("/api/children/{$child->id}/today")->assertForbidden();

        $this->postJson("/api/children/{$child->id}/unlock", ['pin' => '1234'])->assertOk();
        $this->getJson("/api/children/{$child->id}/today")->assertOk();
    }

    public function test_complete_task_awards_points_once_and_uncomplete_reverses_once(): void
    {
        [$child] = $this->scheduledTask();
        app(DailyTaskGenerator::class)->generateForChild($child, today());
        $dailyTask = $child->dailyTasks()->first();
        $action = app(CompleteDailyTaskAction::class);

        $first = $action->complete($dailyTask);
        $second = $action->complete($dailyTask);

        $this->assertSame(10, $first['points_awarded']);
        $this->assertSame(0, $second['points_awarded']);
        $this->assertSame(10, (int) $child->pointTransactions()->sum('amount'));

        $action->uncomplete($dailyTask);
        $action->uncomplete($dailyTask);

        $this->assertSame(0, (int) $child->pointTransactions()->sum('amount'));
        $this->assertSame(DailyTaskStatus::PENDING, $dailyTask->refresh()->status);
    }

    public function test_reward_redemption_requires_parent_approval_before_spending_points(): void
    {
        [$child] = $this->scheduledTask();
        $parent = $this->parent();
        $reward = Reward::create(['title' => 'Ice cream', 'required_points' => 10, 'is_active' => true]);

        $this->actingAs($parent)->postJson("/api/children/{$child->id}/rewards/{$reward->id}/redeem")
            ->assertUnprocessable();

        app(DailyTaskGenerator::class)->generateForChild($child, today());
        app(CompleteDailyTaskAction::class)->complete($child->dailyTasks()->first());

        $redemptionId = $this->actingAs($parent)->postJson("/api/children/{$child->id}/rewards/{$reward->id}/redeem")
            ->assertOk()
            ->assertJsonPath('redemption.points_spent', 10)
            ->assertJsonPath('redemption.status', 'pending')
            ->json('redemption.id');

        $this->assertSame(10, (int) $child->pointTransactions()->sum('amount'));

        $this->actingAs($parent)->patchJson("/api/parent/reward-history/{$redemptionId}/approve")
            ->assertOk()
            ->assertJsonPath('redemption.status', 'approved');

        $this->actingAs($parent)->patchJson("/api/parent/reward-history/{$redemptionId}/approve")
            ->assertUnprocessable();

        $this->assertSame(0, (int) $child->pointTransactions()->sum('amount'));
        $this->assertSame(1, $child->rewardRedemptions()->count());
    }

    public function test_parent_can_reject_reward_redemption_without_spending_points(): void
    {
        [$child] = $this->scheduledTask();
        $parent = $this->parent();
        $reward = Reward::create(['title' => 'Ice cream', 'required_points' => 10, 'is_active' => true]);

        app(DailyTaskGenerator::class)->generateForChild($child, today());
        app(CompleteDailyTaskAction::class)->complete($child->dailyTasks()->first());

        $redemptionId = $this->actingAs($parent)->postJson("/api/children/{$child->id}/rewards/{$reward->id}/redeem")
            ->assertOk()
            ->json('redemption.id');

        $this->actingAs($parent)->patchJson("/api/parent/reward-history/{$redemptionId}/reject")
            ->assertOk()
            ->assertJsonPath('redemption.status', 'rejected');

        $this->assertSame(10, (int) $child->pointTransactions()->sum('amount'));
    }

    private function parent(): User
    {
        return User::create([
            'name' => 'Parent',
            'email' => 'test-parent-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::PARENT,
        ]);
    }

    private function scheduledTask(): array
    {
        $child = User::create(['name' => 'Minh', 'role' => UserRole::CHILD, 'child_pin' => Hash::make('1234')]);
        $category = TaskCategory::create(['name' => 'Study', 'icon' => '📚']);
        $task = Task::create([
            'user_id' => $child->id,
            'category_id' => $category->id,
            'title' => 'Read',
            'points' => 10,
            'is_active' => true,
        ]);
        $task->schedule()->create([
            'repeat_type' => RepeatType::DAILY,
            'start_date' => today()->subDay()->toDateString(),
        ]);

        return [$child, $task];
    }
}
