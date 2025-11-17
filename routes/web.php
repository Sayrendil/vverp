<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\DictionaryController;
use App\Http\Controllers\ExecutorController;

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
});
