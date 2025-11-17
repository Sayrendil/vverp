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
