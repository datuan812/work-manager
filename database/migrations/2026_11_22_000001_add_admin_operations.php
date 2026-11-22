<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_task_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('task_categories')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon', 20)->nullable();
            $table->unsignedSmallInteger('points');
            $table->unsignedTinyInteger('min_age')->nullable();
            $table->unsignedTinyInteger('max_age')->nullable();
            $table->json('steps')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('shared_task_template_id')->nullable()->constrained()->nullOnDelete();
            $table->json('steps')->nullable();
            $table->unique(['family_id', 'shared_task_template_id']);
        });

        Schema::create('shared_task_template_imports', function (Blueprint $table): void {
            $table->foreignId('shared_task_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->primary(['shared_task_template_id', 'family_id'], 'shared_template_imports_pk');
        });

        Schema::table('families', function (Blueprint $table): void {
            $table->unsignedBigInteger('access_version')->default(0);
            $table->timestamp('first_task_assigned_at')->nullable();
        });

        DB::table('families')->update(['first_task_assigned_at' => DB::raw(
            '(SELECT MIN(daily_tasks.created_at) FROM daily_tasks WHERE daily_tasks.family_id = families.id)'
        )]);

        Schema::create('parent_pin_resets', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
        });

        Schema::create('family_weekly_activity', function (Blueprint $table): void {
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->primary(['week_start', 'family_id']);
        });

        Schema::create('admin_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name');
            $table->string('action', 64)->index();
            $table->string('subject_type', 32);
            $table->unsignedBigInteger('subject_id');
            $table->string('subject_name');
            $table->json('details')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('family_weekly_activity');
        Schema::dropIfExists('parent_pin_resets');
        Schema::table('families', fn (Blueprint $table) => $table->dropColumn(['access_version', 'first_task_assigned_at']));
        Schema::dropIfExists('shared_task_template_imports');
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropUnique(['family_id', 'shared_task_template_id']);
            $table->dropConstrainedForeignId('shared_task_template_id');
            $table->dropColumn('steps');
        });
        Schema::dropIfExists('shared_task_templates');
    }
};
