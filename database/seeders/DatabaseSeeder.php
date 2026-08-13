<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Achievement;
use App\Models\Reward;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
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
                'password' => Hash::make('12345678'),
                'role' => UserRole::PARENT,
                'avatar' => null,
                'is_active' => true,
            ],
        );

        collect([
            ['name' => 'Học tập', 'icon' => '📚', 'color' => '#2563eb'],
            ['name' => 'Vệ sinh', 'icon' => '🪥', 'color' => '#0f766e'],
            ['name' => 'Việc nhà', 'icon' => '🧹', 'color' => '#b45309'],
            ['name' => 'Sức khỏe', 'icon' => '❤️', 'color' => '#dc2626'],
            ['name' => 'Thói quen', 'icon' => '🌱', 'color' => '#16a34a'],
            ['name' => 'Khác', 'icon' => '⭐', 'color' => '#7c3aed'],
        ])->each(fn (array $category, int $index) => TaskCategory::updateOrCreate(
            ['name' => $category['name']],
            [...$category, 'sort_order' => $index + 1],
        ));

        collect([
            ['name' => 'Tuấn', 'date_of_birth' => '2017-04-12'],
            ['name' => 'Linh', 'date_of_birth' => '2019-09-20'],
            ['name' => 'Tina', 'date_of_birth' => '2021-01-08'],
        ])->each(fn (array $child) => User::updateOrCreate(
            ['name' => $child['name'], 'role' => UserRole::CHILD],
            [
                ...$child,
                'email' => null,
                'password' => null,
                'avatar' => null,
                'is_active' => true,
            ],
        ));

        collect([
            ['code' => 'first_step', 'title' => 'Bước đầu tiên', 'description' => 'Hoàn thành nhiệm vụ đầu tiên.', 'icon' => '🏆'],
            ['code' => 'seven_day_streak', 'title' => '7 ngày bền bỉ', 'description' => 'Hoàn thành toàn bộ nhiệm vụ trong 7 ngày liên tiếp.', 'icon' => '🔥'],
            ['code' => 'task_master', 'title' => 'Cao thủ nhiệm vụ', 'description' => 'Hoàn thành 100 nhiệm vụ.', 'icon' => '⭐'],
            ['code' => 'perfect_day', 'title' => 'Ngày hoàn hảo', 'description' => 'Hoàn thành toàn bộ nhiệm vụ trong một ngày.', 'icon' => '💯'],
            ['code' => 'early_bird', 'title' => 'Chủ động mỗi ngày', 'description' => 'Hoàn thành 10 nhiệm vụ.', 'icon' => '🌟'],
        ])->each(fn (array $achievement) => Achievement::updateOrCreate(
            ['code' => $achievement['code']],
            [...$achievement, 'criteria' => [], 'is_active' => true],
        ));

        collect([
            ['title' => 'Đọc sách 20 phút', 'category' => 'Học tập', 'icon' => '📖', 'points' => 15],
            ['title' => 'Làm 10 bài toán', 'category' => 'Học tập', 'icon' => '➗', 'points' => 20],
            ['title' => 'Luyện tiếng Anh 15 phút', 'category' => 'Học tập', 'icon' => '🔤', 'points' => 20],
            ['title' => 'Viết nhật ký ngắn', 'category' => 'Học tập', 'icon' => '✏️', 'points' => 10],
            ['title' => 'Ôn bài trên lớp', 'category' => 'Học tập', 'icon' => '📚', 'points' => 15],
            ['title' => 'Đánh răng buổi sáng', 'category' => 'Vệ sinh', 'icon' => '🪥', 'points' => 5],
            ['title' => 'Đánh răng buổi tối', 'category' => 'Vệ sinh', 'icon' => '🦷', 'points' => 5],
            ['title' => 'Tắm sạch sẽ', 'category' => 'Vệ sinh', 'icon' => '🚿', 'points' => 5],
            ['title' => 'Rửa tay trước bữa ăn', 'category' => 'Vệ sinh', 'icon' => '🧼', 'points' => 5],
            ['title' => 'Gấp chăn sau khi ngủ dậy', 'category' => 'Việc nhà', 'icon' => '🛏️', 'points' => 10],
            ['title' => 'Dọn bàn học', 'category' => 'Việc nhà', 'icon' => '🧹', 'points' => 10],
            ['title' => 'Xếp đồ chơi gọn gàng', 'category' => 'Việc nhà', 'icon' => '🧸', 'points' => 10],
            ['title' => 'Phụ dọn bát sau bữa ăn', 'category' => 'Việc nhà', 'icon' => '🍽️', 'points' => 15],
            ['title' => 'Tưới cây', 'category' => 'Việc nhà', 'icon' => '🪴', 'points' => 10],
            ['title' => 'Uống đủ nước', 'category' => 'Sức khỏe', 'icon' => '🥤', 'points' => 5],
            ['title' => 'Tập thể dục 10 phút', 'category' => 'Sức khỏe', 'icon' => '🏃', 'points' => 15],
            ['title' => 'Ăn thêm rau', 'category' => 'Sức khỏe', 'icon' => '🥦', 'points' => 10],
            ['title' => 'Chuẩn bị cặp sách', 'category' => 'Thói quen', 'icon' => '🎒', 'points' => 10],
            ['title' => 'Đi ngủ đúng giờ', 'category' => 'Thói quen', 'icon' => '🌙', 'points' => 15],
            ['title' => 'Nói lời cảm ơn', 'category' => 'Thói quen', 'icon' => '💬', 'points' => 5],
        ])->each(function (array $task): void {
            $category = TaskCategory::query()->where('name', $task['category'])->first();

            Task::updateOrCreate(
                ['user_id' => null, 'title' => $task['title']],
                [
                    'category_id' => $category?->id,
                    'description' => 'Nhiệm vụ mẫu có thể giao theo lịch.',
                    'icon' => $task['icon'],
                    'points' => $task['points'],
                    'is_active' => true,
                ],
            );
        });

        collect([
            ['title' => 'Chơi game 30 phút', 'description' => 'Một lượt giải trí sau khi hoàn thành mục tiêu.', 'icon' => '🎮', 'required_points' => 120],
            ['title' => 'Ăn kem cuối tuần', 'description' => 'Một phần kem yêu thích.', 'icon' => '🍦', 'required_points' => 150],
            ['title' => 'Xem phim gia đình', 'description' => 'Chọn một bộ phim xem cùng gia đình.', 'icon' => '🎬', 'required_points' => 220],
            ['title' => 'Mua sách mới', 'description' => 'Chọn một cuốn sách phù hợp.', 'icon' => '📘', 'required_points' => 300],
            ['title' => 'Đi công viên', 'description' => 'Một buổi đi chơi ngoài trời.', 'icon' => '🎡', 'required_points' => 400],
        ])->each(fn (array $reward) => Reward::updateOrCreate(
            ['title' => $reward['title']],
            [...$reward, 'is_active' => true],
        ));
    }
}
