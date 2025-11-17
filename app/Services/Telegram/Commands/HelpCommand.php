<?php

namespace App\Services\Telegram\Commands;

use App\Services\TelegramBotService;
use App\Services\Telegram\CommandRouter;

/**
 * Команда /help - справка по командам
 */
class HelpCommand
{
    public function __construct(
        private TelegramBotService $bot,
        private CommandRouter $router
    ) {}

    public function handle(array $context): void
    {
        $chatId = $context['chatId'] ?? null;

        if (!$chatId) {
            return;
        }

        // Получаем список команд из роутера
        $helpText = $this->router->getHelpText();

        if (empty($helpText)) {
            // Fallback если описания не заданы
            $message = "📖 <b>Справка по командам:</b>\n\n" .
                "/start - Создать заявку\n" .
                "/cancel - Отменить текущую заявку\n" .
                "/skip - Пропустить текущий шаг\n" .
                "/help - Показать эту справку";
        } else {
            $message = "📖 <b>Справка по командам:</b>\n\n";
            foreach ($helpText as $command => $description) {
                $message .= "{$command} - {$description}\n";
            }
        }

        $this->bot->sendMessage($chatId, $message);
    }
}
