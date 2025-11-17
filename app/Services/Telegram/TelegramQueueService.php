<?php

namespace App\Services\Telegram;

use App\Jobs\SendTelegramMessage;
use App\Jobs\SendTelegramMessageWithButtons;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для отправки сообщений в Telegram через очередь
 *
 * Использование:
 * TelegramQueueService::sendMessage($chatId, $text);
 * TelegramQueueService::sendMessageWithButtons($chatId, $text, $buttons);
 */
class TelegramQueueService
{
    /**
     * Отправить текстовое сообщение через очередь
     *
     * @param int|string $chatId ID чата
     * @param string $text Текст сообщения
     * @param array $options Дополнительные параметры
     * @param int $delay Задержка отправки (секунды)
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public static function sendMessage(
        int|string $chatId,
        string $text,
        array $options = [],
        int $delay = 0
    ) {
        if ($delay > 0) {
            return SendTelegramMessage::dispatch($chatId, $text, $options)
                ->delay(now()->addSeconds($delay));
        }

        return SendTelegramMessage::dispatch($chatId, $text, $options);
    }

    /**
     * Отправить сообщение с кнопками через очередь
     *
     * @param int|string $chatId ID чата
     * @param string $text Текст сообщения
     * @param array $buttons Массив кнопок
     * @param int $delay Задержка отправки (секунды)
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public static function sendMessageWithButtons(
        int|string $chatId,
        string $text,
        array $buttons,
        int $delay = 0
    ) {
        if ($delay > 0) {
            return SendTelegramMessageWithButtons::dispatch($chatId, $text, $buttons)
                ->delay(now()->addSeconds($delay));
        }

        return SendTelegramMessageWithButtons::dispatch($chatId, $text, $buttons);
    }

    /**
     * Отправить массовые уведомления через очередь с задержкой
     *
     * @param array $recipients Массив [['chat_id' => ..., 'text' => ...], ...]
     * @param int $delayBetween Задержка между отправками (секунды)
     * @return int Количество поставленных в очередь сообщений
     */
    public static function sendBulk(array $recipients, int $delayBetween = 1): int
    {
        $count = 0;
        $delay = 0;

        foreach ($recipients as $recipient) {
            self::sendMessage(
                $recipient['chat_id'],
                $recipient['text'],
                $recipient['options'] ?? [],
                $delay
            );

            $delay += $delayBetween;
            $count++;
        }

        Log::info("Queued {$count} telegram messages", [
            'delay_between' => $delayBetween,
        ]);

        return $count;
    }

    /**
     * Отправить уведомление на высокоприоритетной очереди
     *
     * @param int|string $chatId
     * @param string $text
     * @param array $options
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public static function sendUrgent(
        int|string $chatId,
        string $text,
        array $options = []
    ) {
        return SendTelegramMessage::dispatch($chatId, $text, $options)
            ->onQueue('telegram-urgent');
    }
}
