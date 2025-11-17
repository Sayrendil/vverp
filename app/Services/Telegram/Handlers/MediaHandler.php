<?php

namespace App\Services\Telegram\Handlers;

use App\Services\TelegramBotService;
use App\Services\TelegramWizardService;
use App\Models\TelegramSession;
use App\Enums\TelegramStep;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик медиа-сообщений (фото, видео, документы)
 *
 * Отвечает за обработку всех медиа-файлов отправленных пользователем
 */
class MediaHandler implements UpdateHandler
{
    public function __construct(
        private TelegramBotService $bot,
        private TelegramWizardService $wizard
    ) {}

    public function supports(array $update): bool
    {
        // Проверяем, содержит ли сообщение медиа
        return isset($update['message']) && (
            isset($update['message']['photo']) ||
            isset($update['message']['video']) ||
            isset($update['message']['document'])
        );
    }

    public function handle(array $update): void
    {
        $message = $update['message'];

        $chatId = $message['chat']['id'] ?? null;
        $userId = $message['from']['id'] ?? null;
        $messageId = $message['message_id'] ?? null;
        $username = $message['from']['username'] ?? 'unknown';

        if (!$chatId || !$userId) {
            return;
        }

        // Проверяем активную сессию
        $session = TelegramSession::where('telegram_user_id', $userId)->first();

        if (!$session || $session->step !== TelegramStep::UPLOAD_FILE) {
            $this->bot->sendMessage($chatId,
                "❌ Сначала используйте /start для создания заявки"
            );
            return;
        }

        // Определяем тип медиа и получаем file_id
        $fileData = $this->extractFileData($message);

        if (!$fileData) {
            $this->bot->sendMessage($chatId, "❌ Не удалось обработать файл");
            return;
        }

        Log::info("Telegram Media: {$fileData['type']} from @{$username} (ID: {$userId})");

        // Сохраняем файл в сессию
        $this->saveAttachment($session, $fileData);

        // Удаляем сообщение пользователя для чистоты чата
        if ($messageId) {
            $this->bot->deleteMessage($chatId, $messageId);
        }

        // Обновляем wizard message с информацией о прикреплённых файлах
        $attachmentCount = count($session->getData('attachments', []));

        // Используем wizard service для обновления сообщения
        $this->wizard->updateAttachmentProgress($session, $chatId, $attachmentCount);
    }

    /**
     * Извлечь данные о файле из сообщения
     */
    private function extractFileData(array $message): ?array
    {
        // Фото (берём наибольшего размера)
        if (isset($message['photo'])) {
            $photos = $message['photo'];
            $largestPhoto = end($photos);

            return [
                'file_id' => $largestPhoto['file_id'],
                'type' => 'photo',
                'size' => $largestPhoto['file_size'] ?? null,
            ];
        }

        // Видео
        if (isset($message['video'])) {
            return [
                'file_id' => $message['video']['file_id'],
                'type' => 'video',
                'size' => $message['video']['file_size'] ?? null,
            ];
        }

        // Документ
        if (isset($message['document'])) {
            return [
                'file_id' => $message['document']['file_id'],
                'type' => 'document',
                'size' => $message['document']['file_size'] ?? null,
                'mime_type' => $message['document']['mime_type'] ?? null,
                'file_name' => $message['document']['file_name'] ?? null,
            ];
        }

        return null;
    }

    /**
     * Сохранить вложение в сессию
     */
    private function saveAttachment(TelegramSession $session, array $fileData): void
    {
        $attachments = $session->getData('attachments', []);

        $attachments[] = [
            'file_id' => $fileData['file_id'],
            'type' => $fileData['type'],
            'size' => $fileData['size'] ?? null,
            'mime_type' => $fileData['mime_type'] ?? null,
            'file_name' => $fileData['file_name'] ?? null,
        ];

        $session->setData('attachments', $attachments);
    }
}
