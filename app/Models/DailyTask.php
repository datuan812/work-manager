<?php

namespace App\Models;

use App\Enums\DailyTaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['task_id', 'user_id', 'date', 'status', 'completed_at'])]
class DailyTask extends Model
{
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
