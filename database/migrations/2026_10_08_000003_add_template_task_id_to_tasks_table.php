<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('template_task_id')
                ->nullable()
                ->after('user_id')
                ->constrained('tasks')
                ->nullOnDelete();

            $table->index(['user_id', 'template_task_id']);
        });

        $templates = DB::table('tasks')
            ->whereNull('user_id')
            ->get(['id', 'title', 'category_id', 'points', 'icon']);

        DB::table('tasks')
            ->whereNotNull('user_id')
            ->whereNull('template_task_id')
            ->orderBy('id')
            ->get(['id', 'title', 'category_id', 'points', 'icon'])
            ->each(function ($task) use ($templates): void {
                $template = $templates->first(fn ($candidate) => $candidate->title === $task->title
                    && (int) $candidate->category_id === (int) $task->category_id
                    && (int) $candidate->points === (int) $task->points
                    && (string) $candidate->icon === (string) $task->icon);

                if (! $template) {
                    return;
                }

                DB::table('tasks')
                    ->where('id', $task->id)
                    ->update(['template_task_id' => $template->id]);
            });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'template_task_id']);
            $table->dropConstrainedForeignId('template_task_id');
        });
    }
};
