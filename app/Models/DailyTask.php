<?php

namespace App\Models;

use App\Enums\DailyTaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToFamily;

#[Fillable(['family_id', 'task_id', 'user_id', 'date', 'reminder_time', 'status', 'completed_at'])]
class DailyTask extends Model
{
    use BelongsToFamily;

    protected static function booted(): void
    {
        static::created(function (DailyTask $task): void {
            if ($task->family_id !== null) {
                Family::query()->whereKey($task->family_id)->whereNull('first_task_assigned_at')
                    ->update(['first_task_assigned_at' => $task->created_at]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'completed_at' => 'datetime',
            'status' => DailyTaskStatus::class,
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
