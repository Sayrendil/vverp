<?php

namespace App\Services\Telegram\Commands;

use App\Services\TelegramBotService;
use App\Services\TelegramWizardService;
use App\Models\TelegramSession;

/**
 * Команда /cancel - отмена создания заявки
 */
class CancelCommand
{
    public function __construct(
        private TelegramBotService $bot,
        private TelegramWizardService $wizard
    ) {}

    public function handle(array $context): void
    {
        $userId = $context['userId'] ?? null;
        $chatId = $context['chatId'] ?? null;

        if (!$userId || !$chatId) {
            return;
        }

        $session = TelegramSession::where('telegram_user_id', $userId)->first();

        if ($session) {
            $this->wizard->cancel($session, $chatId);
        } else {
            $this->bot->sendMessage($chatId, "❌ Нет активной заявки для отмены");
        }
    }
}
