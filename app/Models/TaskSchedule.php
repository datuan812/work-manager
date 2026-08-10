<?php

namespace App\Models;

use App\Enums\RepeatType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['task_id', 'repeat_type', 'start_date', 'end_date', 'days_of_week', 'time_of_day'])]
class TaskSchedule extends Model
{
    protected function casts(): array
    {
        return [
            'repeat_type' => RepeatType::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'days_of_week' => 'array',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function occursOn(CarbonInterface $date): bool
    {
        if ($date->lt($this->start_date) || ($this->end_date && $date->gt($this->end_date))) {
            return false;
        }

        return match ($this->repeat_type) {
            RepeatType::ONCE => $date->isSameDay($this->start_date),
            RepeatType::DAILY => true,
            RepeatType::WEEKLY => $date->dayOfWeekIso === $this->start_date->dayOfWeekIso,
            RepeatType::CUSTOM_DAYS => in_array($date->dayOfWeekIso, $this->days_of_week ?? [], true),
        };
    }
}
