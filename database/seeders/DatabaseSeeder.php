<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Family;
use App\Models\Reward;
use App\Models\Task;
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
        $family = Family::firstOrCreate(['code' => 'GD123456'], ['name' => 'Gia đình mẫu', 'is_active' => true]);
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Đinh Anh Tuấn',
                'password' => Hash::make('12345678'),
                'role' => UserRole::ADMIN,
                'family_id' => null,
                'avatar' => null,
                'is_active' => true,
            ],
        );
        User::updateOrCreate(['email' => 'parent@gmail.com'], ['name' => 'Phụ huynh mẫu', 'password' => Hash::make('12345678'), 'role' => UserRole::PARENT, 'family_id' => $family->id, 'is_active' => true]);

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
                'child_pin' => Hash::make('1234'),
                'avatar' => null,
                'is_active' => true,
                'family_id' => $family->id,
            ],
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
        ])->each(function (array $task) use ($family): void {
            Task::updateOrCreate(
                ['user_id' => null, 'title' => $task['title']],
                [
                    'family_id' => $family->id,
                    'category_id' => null,
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
            [...$reward, 'is_active' => true, 'family_id' => $family->id],
        ));
    }
}
