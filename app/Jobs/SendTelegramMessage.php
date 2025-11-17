<?php

namespace App\Jobs;

use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job для асинхронной отправки сообщений в Telegram
 *
 * Преимущества:
 * - Пользователь не ждет отправки (мгновенный ответ)
 * - Автоматические повторы при ошибках
 * - Защита от перегрузки API
 */
class SendTelegramMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Количество попыток выполнения задачи
     */
    public int $tries = 3;

    /**
     * Таймаут выполнения (секунды)
     */
    public int $timeout = 60;

    /**
     * Задержка перед повторными попытками (секунды)
     */
    public array $backoff = [10, 30, 60];

    /**
     * @param int|string $chatId ID чата
     * @param string $text Текст сообщения
     * @param array $options Дополнительные параметры (кнопки, форматирование)
     */
    public function __construct(
        private int|string $chatId,
        private string $text,
        private array $options = []
    ) {
        // Устанавливаем очередь через метод
        $this->onQueue('telegram');
    }

    /**
     * Выполнить задачу
     */
    public function handle(TelegramBotService $bot): void
    {
        try {
            $result = $bot->sendMessage($this->chatId, $this->text, $this->options);

            if ($result) {
                Log::info('Telegram message sent via queue', [
                    'chat_id' => $this->chatId,
                    'message_id' => $result['message_id'] ?? null,
                    'attempt' => $this->attempts(),
                ]);
            } else {
                throw new \Exception('Failed to send message (no result)');
            }
        } catch (\Exception $e) {
            Log::error('Failed to send telegram message', [
                'chat_id' => $this->chatId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            // Пробрасываем исключение для автоматического retry
            throw $e;
        }
    }

    /**
     * Обработка ошибки после всех попыток
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendTelegramMessage job failed after all retries', [
            'chat_id' => $this->chatId,
            'text' => mb_substr($this->text, 0, 100),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Здесь можно добавить уведомление администратору
        // или сохранение неотправленного сообщения в БД
    }

    /**
     * Получить теги для мониторинга
     */
    public function tags(): array
    {
        return ['telegram', 'chat:' . $this->chatId];
    }
}
