<?php

namespace Database\Seeders;

use App\Actions\CompleteDailyTaskAction;
use App\Enums\RepeatType;
use App\Enums\UserRole;
use App\Models\Achievement;
use App\Models\Reward;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use App\Services\DailyTaskGenerator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Đinh Anh Tuấn',
                'password' => Hash::make('password'),
                'role' => UserRole::PARENT,
                'avatar' => null,
                'is_active' => true,
            ],
        );

        $children = collect([
            ['name' => 'Minh', 'avatar' => null, 'date_of_birth' => '2018-05-12'],
            ['name' => 'An', 'avatar' => null, 'date_of_birth' => '2019-09-03'],
            ['name' => 'Nam', 'avatar' => null, 'date_of_birth' => '2020-02-21'],
        ])->map(fn (array $child) => User::updateOrCreate(
            ['name' => $child['name'], 'role' => UserRole::CHILD],
            [
                ...$child,
                'email' => null,
                'password' => null,
                'is_active' => true,
            ],
        ));

        $categories = collect([
            ['name' => 'Học tập', 'icon' => '📚', 'color' => '#2563eb'],
            ['name' => 'Vệ sinh', 'icon' => '🪥', 'color' => '#0f766e'],
            ['name' => 'Việc nhà', 'icon' => '🧹', 'color' => '#b45309'],
            ['name' => 'Sức khỏe', 'icon' => '❤️', 'color' => '#dc2626'],
            ['name' => 'Thói quen', 'icon' => '🌱', 'color' => '#16a34a'],
            ['name' => 'Khác', 'icon' => '⭐', 'color' => '#7c3aed'],
        ])->map(fn (array $category, int $index) => TaskCategory::updateOrCreate(
            ['name' => $category['name']],
            [...$category, 'sort_order' => $index + 1],
        ));

        $taskPlans = [
            'Minh' => [
                ['Đánh răng', 'Vệ sinh', '🪥', 5],
                ['Học bài', 'Học tập', '📚', 15],
                ['Dọn đồ chơi', 'Việc nhà', '🧸', 10],
                ['Đọc sách', 'Học tập', '📖', 10],
                ['Dọn giường', 'Thói quen', '🛏️', 5],
            ],
            'An' => [
                ['Chuẩn bị cặp sách', 'Thói quen', '🎒', 10],
                ['Đọc sách', 'Học tập', '📖', 10],
                ['Tắm', 'Vệ sinh', '🚿', 5],
                ['Dọn phòng', 'Việc nhà', '🧹', 15],
            ],
            'Nam' => [
                ['Đánh răng', 'Vệ sinh', '🪥', 5],
                ['Xếp đồ chơi', 'Việc nhà', '🧸', 10],
                ['Uống nước', 'Sức khỏe', '🥤', 5],
            ],
        ];

        $children->each(function (User $child) use ($taskPlans, $categories): void {
            foreach ($taskPlans[$child->name] as [$title, $categoryName, $icon, $points]) {
                $task = Task::updateOrCreate(
                    ['user_id' => $child->id, 'title' => $title],
                    [
                        'category_id' => $categories->firstWhere('name', $categoryName)->id,
                        'description' => 'Nhiệm vụ hằng ngày',
                        'icon' => $icon,
                        'points' => $points,
                        'is_active' => true,
                    ],
                );

                $task->schedule()->updateOrCreate(
                    ['task_id' => $task->id],
                    [
                        'repeat_type' => RepeatType::DAILY,
                        'start_date' => now()->subWeek()->toDateString(),
                        'days_of_week' => null,
                        'time_of_day' => null,
                    ],
                );
            }
        });

        collect([
            ['code' => 'first_step', 'title' => 'First Step', 'description' => 'Hoàn thành nhiệm vụ đầu tiên.', 'icon' => '🏆'],
            ['code' => 'seven_day_streak', 'title' => '7 Day Streak', 'description' => 'Hoàn thành nhiệm vụ 7 ngày liên tiếp.', 'icon' => '🔥'],
            ['code' => 'task_master', 'title' => 'Task Master', 'description' => 'Hoàn thành 100 nhiệm vụ.', 'icon' => '⭐'],
            ['code' => 'perfect_day', 'title' => 'Perfect Day', 'description' => 'Hoàn thành toàn bộ nhiệm vụ trong ngày.', 'icon' => '💯'],
        ])->each(fn (array $achievement) => Achievement::updateOrCreate(
            ['code' => $achievement['code']],
            [...$achievement, 'criteria' => []],
        ));

        collect([
            ['title' => 'Chơi game 30 phút', 'description' => 'Một lượt giải trí sau khi hoàn thành mục tiêu.', 'icon' => '🎮', 'required_points' => 100],
            ['title' => 'Ăn kem', 'description' => 'Một phần kem yêu thích.', 'icon' => '🍦', 'required_points' => 150],
            ['title' => 'Xem phim', 'description' => 'Chọn phim gia đình cuối tuần.', 'icon' => '🎬', 'required_points' => 200],
        ])->each(fn (array $reward) => Reward::updateOrCreate(
            ['title' => $reward['title']],
            [...$reward, 'is_active' => true],
        ));

        $generator = app(DailyTaskGenerator::class);
        $complete = app(CompleteDailyTaskAction::class);

        $children->each(fn (User $child) => $generator->generateForChild($child, today()));

        $children->firstWhere('name', 'Minh')->dailyTasks()->take(3)->get()->each(fn ($dailyTask) => $complete->complete($dailyTask));
        $children->firstWhere('name', 'An')->dailyTasks()->take(2)->get()->each(fn ($dailyTask) => $complete->complete($dailyTask));
    }
}
