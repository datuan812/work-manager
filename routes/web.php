<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminOperationsController;
use App\Http\Controllers\Api\AdminTaskTemplateController;
use App\Http\Controllers\Api\ChildController;
use App\Http\Controllers\Api\DailyTaskController;
use App\Http\Controllers\Api\FamilySupportController;
use App\Http\Controllers\Api\Parent\ChildManagementController;
use App\Http\Controllers\Api\Parent\ParentController;
use App\Http\Controllers\Api\Parent\RewardHistoryController;
use App\Http\Controllers\Api\Parent\RewardManagementController;
use App\Http\Controllers\Api\Parent\SharedTaskTemplateController;
use App\Http\Controllers\Api\Parent\TaskCalendarController;
use App\Http\Controllers\Api\Parent\TaskHistoryController;
use App\Http\Controllers\Api\Parent\TaskManagementController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::prefix('api')->group(function () {
    Route::get('children', [ChildController::class, 'index'])->middleware('family.session');
    Route::post('children/{user}/unlock', [ChildController::class, 'unlock'])->middleware(['throttle:15,1,child-pin', 'family.session']);

    Route::middleware(['family.session', 'child.access'])->group(function () {
        Route::get('children/{user}/today', [ChildController::class, 'today']);
        Route::get('children/{user}/daily-tasks', [ChildController::class, 'dailyTasks']);
        Route::patch('children/{user}/daily-tasks/draft', [ChildController::class, 'saveDailyTaskDraft']);
        Route::post('children/{user}/daily-tasks/submit', [ChildController::class, 'submitDailyTasks']);
        Route::get('children/{user}/progress', [ChildController::class, 'progress']);
        Route::get('children/{user}/rewards', [ChildController::class, 'rewards']);
        Route::get('children/{user}/reward-history', [ChildController::class, 'rewardHistory']);
        Route::post('children/{user}/rewards/{reward}/redeem', [ChildController::class, 'redeem']);

        Route::patch('daily-tasks/{dailyTask}/complete', [DailyTaskController::class, 'complete']);
        Route::patch('daily-tasks/{dailyTask}/uncomplete', [DailyTaskController::class, 'uncomplete']);
    });

    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:15,1,email-login');
    Route::post('auth/family-login', [AuthController::class, 'familyLogin'])->middleware('throttle:15,1,family-login');
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/reset-parent-pin', [FamilySupportController::class, 'resetPin'])->middleware('throttle:8,1,pin-recovery');
    Route::get('auth/me', [AuthController::class, 'me'])->middleware(['auth', 'family.session']);

    Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
        Route::get('dashboard', [AdminOperationsController::class, 'dashboard']);
        Route::get('audit-logs', [AdminOperationsController::class, 'auditLogs']);
        Route::apiResource('task-templates', AdminTaskTemplateController::class)
            ->parameters(['task-templates' => 'taskTemplate'])->only(['index', 'store', 'update', 'destroy']);
        Route::get('users', [AdminController::class, 'users']);
        Route::patch('users/{user}', [AdminController::class, 'updateUser']);
        Route::get('families', [AdminController::class, 'families']);
        Route::post('families', [AdminController::class, 'storeFamily']);
        Route::patch('families/{family}', [AdminController::class, 'updateFamily']);
        Route::delete('families/{family}', [AdminController::class, 'destroyFamily']);
        Route::get('families/{family}/parents', [FamilySupportController::class, 'parents']);
        Route::post('families/{family}/rotate-code', [FamilySupportController::class, 'rotateCode']);
        Route::post('families/{family}/revoke-sessions', [FamilySupportController::class, 'revokeSessions']);
        Route::post('families/{family}/pin-resets', [FamilySupportController::class, 'issuePinReset']);
        Route::get('categories', [AdminController::class, 'categories']);
        Route::post('categories', [AdminController::class, 'storeCategory']);
        Route::patch('categories/{category}', [AdminController::class, 'updateCategory']);
        Route::delete('categories/{category}', [AdminController::class, 'destroyCategory']);
    });

    Route::middleware(['auth', 'family.session', 'parent'])->prefix('parent')->group(function () {
        Route::get('access-pin', [ParentController::class, 'accessPinStatus']);
        Route::put('access-pin', [ParentController::class, 'setAccessPin'])->middleware('throttle:10,1,parent-pin');
        Route::patch('access-pin/toggle', [ParentController::class, 'toggleAccessPin'])->middleware('throttle:10,1,parent-pin');
        Route::post('access-pin/verify', [ParentController::class, 'verifyAccessPin'])->middleware('throttle:10,1,parent-pin');
        Route::middleware('parent.access')->group(function () {
        Route::get('task-templates', [SharedTaskTemplateController::class, 'index']);
        Route::post('task-templates/{taskTemplate}/import', [SharedTaskTemplateController::class, 'import']);
        Route::get('/', [ParentController::class, 'dashboard']);
        Route::get('statistics', [ParentController::class, 'statistics']);
        Route::apiResource('children', ChildManagementController::class)->parameters(['children' => 'user']);
        Route::get('task-calendar', [TaskCalendarController::class, 'index']);
        Route::post('task-calendar', [TaskCalendarController::class, 'store']);
        Route::delete('task-calendar/{dailyTask}', [TaskCalendarController::class, 'destroy']);
        Route::get('task-history', [TaskHistoryController::class, 'index']);
        Route::get('reward-history', [RewardHistoryController::class, 'index']);
        Route::patch('reward-history/{redemption}/approve', [RewardHistoryController::class, 'approve']);
        Route::patch('reward-history/{redemption}/reject', [RewardHistoryController::class, 'reject']);
        Route::apiResource('tasks', TaskManagementController::class)->except(['show']);
        Route::apiResource('rewards', RewardManagementController::class)->except(['show']);
        });
    });
});

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
