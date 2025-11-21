<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\DictionaryController;
use App\Http\Controllers\ExecutorController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\MonitoringController;

/*
|--------------------------------------------------------------------------
| Публичные маршруты
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Healthcheck endpoint для мониторинга (без авторизации)
Route::get('/monitoring/healthcheck', [MonitoringController::class, 'healthcheck'])
    ->name('monitoring.healthcheck');

/*
|--------------------------------------------------------------------------
| Защищенные маршруты (требуется авторизация)
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Главная панель
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Заявки
    Route::resource('tickets', TicketController::class);
    Route::put('/tickets/{id}/status', [TicketController::class, 'updateStatus'])->name('tickets.update-status');

    // Workflow заявок
    Route::post('/tickets/{id}/take-to-work', [TicketController::class, 'takeToWork'])->name('tickets.take-to-work');
    Route::post('/tickets/{id}/postpone', [TicketController::class, 'postpone'])->name('tickets.postpone');
    Route::post('/tickets/{id}/send-for-confirmation', [TicketController::class, 'sendForConfirmation'])->name('tickets.send-for-confirmation');
    Route::post('/tickets/{id}/confirm-completion', [TicketController::class, 'confirmCompletion'])->name('tickets.confirm-completion');
    Route::post('/tickets/{id}/return-to-work', [TicketController::class, 'returnToWork'])->name('tickets.return-to-work');

    // Админские действия с заявками
    Route::post('/tickets/{id}/assign-executor', [TicketController::class, 'assignExecutor'])->name('tickets.assign-executor');
    Route::post('/tickets/{id}/unassign-executor', [TicketController::class, 'unassignExecutor'])->name('tickets.unassign-executor');
    Route::post('/tickets/{id}/admin-change-status', [TicketController::class, 'adminChangeStatus'])->name('tickets.admin-change-status');

    /*
    |--------------------------------------------------------------------------
    | Исполнители (только для администраторов)
    |--------------------------------------------------------------------------
    */
    Route::middleware(\App\Http\Middleware\EnsureUserIsAdmin::class)
        ->prefix('executors')
        ->name('executors.')
        ->group(function () {
            Route::get('/', [ExecutorController::class, 'index'])->name('index');
            Route::get('/available/{categoryId}', [ExecutorController::class, 'available'])->name('available');
            Route::post('/', [ExecutorController::class, 'store'])->name('store');
            Route::put('/{userId}/{categoryId}', [ExecutorController::class, 'update'])->name('update');
            Route::post('/{userId}/{categoryId}/toggle', [ExecutorController::class, 'toggle'])->name('toggle');
            Route::delete('/{userId}/{categoryId}', [ExecutorController::class, 'destroy'])->name('destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Справочники (только для администраторов)
    |--------------------------------------------------------------------------
    */
    Route::middleware(\App\Http\Middleware\EnsureUserIsAdmin::class)
        ->prefix('dictionaries')
        ->name('dictionaries.')
        ->group(function () {
            // Список всех справочников
            Route::get('/', [DictionaryController::class, 'index'])->name('index');

            // Управление конкретным справочником
            Route::get('/{dictionary}', [DictionaryController::class, 'show'])->name('show');
            Route::post('/{dictionary}', [DictionaryController::class, 'store'])->name('store');
            Route::put('/{dictionary}/{id}', [DictionaryController::class, 'update'])->name('update');
            Route::delete('/{dictionary}/{id}', [DictionaryController::class, 'destroy'])->name('destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Мониторинг хостов (только для администраторов)
    |--------------------------------------------------------------------------
    */
    Route::middleware(\App\Http\Middleware\EnsureUserIsAdmin::class)
        ->prefix('monitoring')
        ->name('monitoring.')
        ->group(function () {
            // Дашборд мониторинга
            Route::get('/', [MonitoringController::class, 'index'])->name('index');

            // Детальная информация по хосту
            Route::get('/hosts/{host}', [MonitoringController::class, 'show'])->name('hosts.show');

            // API для проверок
            Route::post('/check-host/{host}', [MonitoringController::class, 'checkHost'])->name('check-host');
            Route::post('/check-store/{store}', [MonitoringController::class, 'checkStoreHosts'])->name('check-store');
            Route::post('/check-all', [MonitoringController::class, 'checkAllHosts'])->name('check-all');

            // API для получения статистики (для обновления через AJAX)
            Route::get('/api/statistics', [MonitoringController::class, 'getStatistics'])->name('api.statistics');
            Route::get('/api/hosts/{host}/statistics', [MonitoringController::class, 'getHostStatistics'])->name('api.host-statistics');
        });

    /*
    |--------------------------------------------------------------------------
    | Модуль управления задачами (Tasks)
    |--------------------------------------------------------------------------
    */

    // Проекты
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('/create', [ProjectController::class, 'create'])->name('create');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::get('/{project:key}', [ProjectController::class, 'show'])->name('show');
        Route::get('/{project:key}/edit', [ProjectController::class, 'edit'])->name('edit');
        Route::put('/{project:key}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/{project:key}', [ProjectController::class, 'destroy'])->name('destroy');

        // Настройки проекта
        Route::get('/{project:key}/settings', [ProjectController::class, 'settings'])->name('settings');

        // Управление участниками проекта
        Route::post('/{project:key}/members', [ProjectMemberController::class, 'store'])->name('members.store');
        Route::put('/{project:key}/members/{user}', [ProjectMemberController::class, 'updateRole'])->name('members.update-role');
        Route::delete('/{project:key}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('members.destroy');
    });

    // Задачи
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/{task}', [TaskController::class, 'show'])->name('show');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::put('/{task}', [TaskController::class, 'update'])->name('update');
        Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');

        // Быстрые действия с задачами
        Route::post('/{task}/status', [TaskController::class, 'updateStatus'])->name('update-status');
        Route::post('/{task}/assignee', [TaskController::class, 'updateAssignee'])->name('update-assignee');
        Route::post('/{task}/priority', [TaskController::class, 'updatePriority'])->name('update-priority');

        // Комментарии
        Route::post('/{task}/comments', [TaskCommentController::class, 'store'])->name('comments.store');
        Route::delete('/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('comments.destroy');

        // Вложения
        Route::post('/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('attachments.store');
        Route::delete('/attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])->name('attachments.destroy');
    });
});
