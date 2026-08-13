<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChildController;
use App\Http\Controllers\Api\DailyTaskController;
use App\Http\Controllers\Api\Parent\AchievementController;
use App\Http\Controllers\Api\Parent\ChildManagementController;
use App\Http\Controllers\Api\Parent\ParentController;
use App\Http\Controllers\Api\Parent\RewardManagementController;
use App\Http\Controllers\Api\Parent\TaskCalendarController;
use App\Http\Controllers\Api\Parent\TaskHistoryController;
use App\Http\Controllers\Api\Parent\TaskManagementController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::prefix('api')->group(function () {
    Route::get('children', [ChildController::class, 'index']);
    Route::get('children/{user}/today', [ChildController::class, 'today']);
    Route::get('children/{user}/progress', [ChildController::class, 'progress']);
    Route::get('children/{user}/achievements', [ChildController::class, 'achievements']);
    Route::get('children/{user}/rewards', [ChildController::class, 'rewards']);
    Route::post('children/{user}/rewards/{reward}/redeem', [ChildController::class, 'redeem']);

    Route::patch('daily-tasks/{dailyTask}/complete', [DailyTaskController::class, 'complete']);
    Route::patch('daily-tasks/{dailyTask}/uncomplete', [DailyTaskController::class, 'uncomplete']);

    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me'])->middleware('auth');

    Route::middleware(['auth', 'parent'])->prefix('parent')->group(function () {
        Route::get('/', [ParentController::class, 'dashboard']);
        Route::get('statistics', [ParentController::class, 'statistics']);
        Route::apiResource('children', ChildManagementController::class)->parameters(['children' => 'user']);
        Route::get('task-calendar', [TaskCalendarController::class, 'index']);
        Route::post('task-calendar', [TaskCalendarController::class, 'store']);
        Route::delete('task-calendar/{dailyTask}', [TaskCalendarController::class, 'destroy']);
        Route::get('task-history', [TaskHistoryController::class, 'index']);
        Route::apiResource('tasks', TaskManagementController::class)->except(['show']);
        Route::apiResource('rewards', RewardManagementController::class)->except(['show']);
        Route::get('achievements', [AchievementController::class, 'index']);
    });
});

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
