<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('color')->default('#2563eb');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('task_categories')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('points')->default(5);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index('category_id');
        });

        Schema::create('task_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('repeat_type');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->json('days_of_week')->nullable();
            $table->time('time_of_day')->nullable();
            $table->timestamps();

            $table->index('task_id');
        });

        Schema::create('daily_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status')->default('pending')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'date']);
            $table->index(['user_id', 'date']);
            $table->index('task_id');
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('required_points');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reward_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('points_spent');
            $table->timestamp('redeemed_at');
            $table->timestamps();

            $table->index(['user_id', 'redeemed_at']);
        });

        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->json('criteria')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->unique(['user_id', 'achievement_id']);
        });

        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->string('type');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('daily_tasks');
        Schema::dropIfExists('task_schedules');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_categories');
    }
};
