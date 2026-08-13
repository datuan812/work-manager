<?php

use App\Enums\DailyTaskStatus;
use App\Models\DailyTask;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tasks:skip-overdue', function () {
    $today = today('Asia/Ho_Chi_Minh')->toDateString();

    $count = DailyTask::query()
        ->where('status', DailyTaskStatus::PENDING->value)
        ->whereDate('date', '<=', $today)
        ->update(['status' => DailyTaskStatus::SKIPPED->value]);

    $this->info("Skipped {$count} overdue task(s).");
})->purpose('Skip pending tasks when their due day ends');

Schedule::command('tasks:skip-overdue')
    ->dailyAt('23:59')
    ->timezone('Asia/Ho_Chi_Minh');
