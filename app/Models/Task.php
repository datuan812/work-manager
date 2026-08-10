<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'category_id', 'title', 'description', 'icon', 'points', 'is_active'])]
class Task extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'points' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'category_id');
    }

    public function schedule(): HasOne
    {
        return $this->hasOne(TaskSchedule::class);
    }

    public function dailyTasks(): HasMany
    {
        return $this->hasMany(DailyTask::class);
    }
}
