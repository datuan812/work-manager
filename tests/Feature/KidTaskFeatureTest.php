<?php

namespace Tests\Feature;

use App\Actions\CompleteDailyTaskAction;
use App\Enums\DailyTaskStatus;
use App\Enums\RepeatType;
use App\Enums\UserRole;
use App\Models\Achievement;
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
            'password' => Hash::make('password'),
            'role' => UserRole::PARENT,
        ]);

        $this->postJson('/api/auth/login', ['email' => 'login-parent@example.com', 'password' => 'password'])
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

    public function test_complete_task_awards_points_once_and_uncomplete_reverses_once(): void
    {
        [$child] = $this->scheduledTask();
        Achievement::firstOrCreate(['code' => 'first_step'], ['title' => 'First Step']);
        app(DailyTaskGenerator::class)->generateForChild($child, today());
        $dailyTask = $child->dailyTasks()->first();
        $action = app(CompleteDailyTaskAction::class);

        $first = $action->complete($dailyTask);
        $second = $action->complete($dailyTask);

        $this->assertSame(10, $first['points_awarded']);
        $this->assertSame(0, $second['points_awarded']);
        $this->assertSame(10, (int) $child->pointTransactions()->sum('amount'));
        $this->assertDatabaseHas('user_achievements', ['user_id' => $child->id]);

        $action->uncomplete($dailyTask);
        $action->uncomplete($dailyTask);

        $this->assertSame(0, (int) $child->pointTransactions()->sum('amount'));
        $this->assertSame(DailyTaskStatus::PENDING, $dailyTask->refresh()->status);
    }

    public function test_reward_redemption_checks_balance(): void
    {
        [$child] = $this->scheduledTask();
        $reward = Reward::create(['title' => 'Ice cream', 'required_points' => 10, 'is_active' => true]);

        $this->postJson("/api/children/{$child->id}/rewards/{$reward->id}/redeem")
            ->assertUnprocessable();

        app(DailyTaskGenerator::class)->generateForChild($child, today());
        app(CompleteDailyTaskAction::class)->complete($child->dailyTasks()->first());

        $this->postJson("/api/children/{$child->id}/rewards/{$reward->id}/redeem")
            ->assertOk()
            ->assertJsonPath('redemption.points_spent', 10);

        $this->assertSame(0, (int) $child->pointTransactions()->sum('amount'));
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
        $child = User::create(['name' => 'Minh', 'role' => UserRole::CHILD]);
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
