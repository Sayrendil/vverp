<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Автоматическая очистка устаревших Telegram сессий каждый час
Schedule::command('telegram:clean-sessions --force')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Мониторинг доступности хостов - проверка по расписанию каждые 5 минут
Schedule::command('monitoring:check-hosts')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Monitoring: scheduled checks completed');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Monitoring: scheduled checks failed');
    });

// Очистка старых логов мониторинга каждую ночь
Schedule::command('monitoring:clean-logs --days=30')
    ->daily()
    ->at('03:00')
    ->withoutOverlapping()
    ->runInBackground();
