<?php

namespace App\Services\Telegram\Commands;

use App\Services\TelegramWizardService;

/**
 * Команда /start - начало создания заявки
 */
class StartCommand
{
    public function __construct(
        private TelegramWizardService $wizard
    ) {}

    public function handle(array $context): void
    {
        $userId = $context['userId'] ?? null;
        $chatId = $context['chatId'] ?? null;

        if (!$userId || !$chatId) {
            return;
        }

        $this->wizard->start((string)$userId, $chatId);
    }
}
