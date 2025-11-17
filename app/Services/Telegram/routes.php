<?php

use App\Services\Telegram\CommandRouter;
use App\Services\Telegram\Commands\StartCommand;
use App\Services\Telegram\Commands\CancelCommand;
use App\Services\Telegram\Commands\SkipCommand;
use App\Services\Telegram\Commands\HelpCommand;
use App\Services\Telegram\Middleware\RateLimitMiddleware;
use App\Services\Telegram\Middleware\CommandRateLimitMiddleware;
use App\Services\Telegram\Middleware\AntiSpamMiddleware;

/**
 * Регистрация команд Telegram бота
 *
 * Работает по аналогии с routes/web.php
 */
return function (CommandRouter $router) {

    // Глобальный middleware для всех команд
    // $router->middleware(new RateLimitMiddleware(app(TelegramBotService::class)));

    // Команда /start - начало создания заявки
    $router->command('/start', [StartCommand::class, 'handle'])
        ->description('Создать новую заявку');

    // Команда /cancel - отмена создания заявки
    $router->command('/cancel', [CancelCommand::class, 'handle'])
        ->description('Отменить создание заявки');

    // Команда /skip - пропустить текущий шаг
    $router->command('/skip', [SkipCommand::class, 'handle'])
        ->description('Пропустить текущий шаг');

    // Команда /help - справка
    $router->command('/help', [HelpCommand::class, 'handle'])
        ->description('Показать справку по командам');

    // ===== Примеры использования middleware =====

    // Пример: команда с rate limiting
    // $router->command('/report', [ReportCommand::class, 'handle'])
    //     ->middleware(new CommandRateLimitMiddleware(
    //         app(TelegramBotService::class),
    //         maxAttempts: 3,  // 3 попытки
    //         decayMinutes: 5   // за 5 минут
    //     ))
    //     ->description('Получить отчет');

    // Пример: админ команда с проверкой прав
    // $router->command('/admin', [AdminCommand::class, 'handle'])
    //     ->middleware(function($context, $next) {
    //         $user = User::where('telegram_id', $context['userId'])->first();
    //         if (!$user || !$user->isAdmin()) {
    //             app(TelegramBotService::class)->sendMessage(
    //                 $context['chatId'],
    //                 '🚫 У вас нет прав для выполнения этой команды'
    //             );
    //             return null;
    //         }
    //         return $next($context);
    //     })
    //     ->description('Админ панель');

    // Пример: команда с несколькими middleware
    // $router->command('/broadcast', [BroadcastCommand::class, 'handle'])
    //     ->middleware([
    //         new RateLimitMiddleware(app(TelegramBotService::class)),
    //         new AntiSpamMiddleware(app(TelegramBotService::class)),
    //         function($context, $next) {
    //             // Дополнительная проверка
    //             return $next($context);
    //         }
    //     ])
    //     ->description('Массовая рассылка');

    // Пример группы команд с общим middleware
    // $router->group(['middleware' => [new RateLimitMiddleware(...)]], function($router) {
    //     $router->command('/profile', [ProfileCommand::class, 'handle']);
    //     $router->command('/settings', [SettingsCommand::class, 'handle']);
    // });
};
