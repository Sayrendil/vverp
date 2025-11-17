<?php

namespace App\Services\Telegram\Handlers;

use App\Services\TelegramBotService;
use App\Services\Telegram\CommandRouter;
use App\Services\Telegram\CommandNotFoundException;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик команд бота (/start, /help, /cancel и т.д.)
 *
 * Отвечает за обработку всех команд, начинающихся с /
 * Использует CommandRouter для диспетчеризации команд
 */
class CommandHandler implements UpdateHandler
{
    private CommandRouter $router;

    public function __construct(
        private TelegramBotService $bot
    ) {
        // Инициализируем роутер и загружаем команды
        $this->router = new CommandRouter();
        $this->loadRoutes();
    }

    public function supports(array $update): bool
    {
        // Проверяем, есть ли сообщение с текстом начинающимся на /
        return isset($update['message']['text'])
            && str_starts_with($update['message']['text'], '/');
    }

    public function handle(array $update): void
    {
        $message = $update['message'];
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? null;
        $userId = $message['from']['id'] ?? null;
        $username = $message['from']['username'] ?? 'unknown';

        if (!$chatId || !$text || !$userId) {
            return;
        }

        Log::info("Telegram Command from @{$username} (ID: {$userId}): {$text}");

        // Формируем контекст для команды
        $context = [
            'chatId' => $chatId,
            'userId' => $userId,
            'username' => $username,
            'message' => $message,
            'update' => $update,
        ];

        try {
            // Диспетчеризуем команду через роутер
            $this->router->dispatch($text, $context);
        } catch (CommandNotFoundException $e) {
            // Команда не найдена
            $this->bot->sendMessage(
                $chatId,
                "❓ Неизвестная команда.\nИспользуйте /help для справки."
            );
        } catch (\Exception $e) {
            // Другие ошибки
            Log::error('Command execution error', [
                'command' => $text,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->bot->sendMessage(
                $chatId,
                "❌ Произошла ошибка при выполнении команды.\n" .
                "Попробуйте еще раз или обратитесь к администратору."
            );
        }
    }

    /**
     * Загрузить маршруты команд из файла
     */
    private function loadRoutes(): void
    {
        $routesFile = app_path('Services/Telegram/routes.php');

        if (file_exists($routesFile)) {
            $loader = require $routesFile;
            $loader($this->router);
        }
    }
}
