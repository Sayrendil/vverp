<?php

namespace App\Services\Telegram\Handlers;

use App\Services\TelegramBotService;
use App\Services\TelegramWizardService;
use App\Models\TelegramSession;
use App\Enums\TelegramStep;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик текстовых сообщений (не команды)
 *
 * Отвечает за обработку обычных текстовых сообщений
 * в зависимости от текущего шага в wizard'е
 */
class TextMessageHandler implements UpdateHandler
{
    public function __construct(
        private TelegramBotService $bot,
        private TelegramWizardService $wizard
    ) {}

    public function supports(array $update): bool
    {
        // Проверяем, что это текстовое сообщение, но не команда
        return isset($update['message']['text'])
            && !str_starts_with($update['message']['text'], '/');
    }

    public function handle(array $update): void
    {
        $message = $update['message'];

        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? null;
        $userId = $message['from']['id'] ?? null;
        $messageId = $message['message_id'] ?? null;
        $username = $message['from']['username'] ?? 'unknown';

        if (!$chatId || !$text || !$userId) {
            return;
        }

        Log::info("Telegram Message: from @{$username} (ID: {$userId}): " . mb_substr($text, 0, 50));

        // Проверяем активную сессию
        $session = TelegramSession::where('telegram_user_id', $userId)->first();

        if (!$session || $session->step === TelegramStep::IDLE) {
            $this->bot->sendMessage($chatId,
                "Используйте /start для создания заявки"
            );
            return;
        }

        // Обрабатываем в зависимости от текущего шага
        $this->handleByStep($session, $text, $chatId, $messageId);
    }

    private function handleByStep(TelegramSession $session, string $text, int $chatId, ?int $messageId): void
    {
        match($session->step) {
            TelegramStep::ENTER_DESCRIPTION => $this->wizard->handleDescription($session, $text, $chatId, $messageId),

            // Для остальных шагов ожидаем кнопки
            default => $this->bot->sendMessage($chatId,
                "Используйте кнопки для выбора или /help для справки"
            ),
        };
    }
}
