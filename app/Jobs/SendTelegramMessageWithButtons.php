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
 * Job для асинхронной отправки сообщений с кнопками в Telegram
 */
class SendTelegramMessageWithButtons implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [10, 30, 60];

    public function __construct(
        private int|string $chatId,
        private string $text,
        private array $buttons
    ) {
        // Устанавливаем очередь через метод
        $this->onQueue('telegram');
    }

    public function handle(TelegramBotService $bot): void
    {
        try {
            $result = $bot->sendMessageWithButtons($this->chatId, $this->text, $this->buttons);

            if ($result) {
                Log::info('Telegram message with buttons sent via queue', [
                    'chat_id' => $this->chatId,
                    'message_id' => $result['message_id'] ?? null,
                ]);
            } else {
                throw new \Exception('Failed to send message with buttons');
            }
        } catch (\Exception $e) {
            Log::error('Failed to send telegram message with buttons', [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendTelegramMessageWithButtons job failed', [
            'chat_id' => $this->chatId,
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return ['telegram', 'chat:' . $this->chatId, 'with_buttons'];
    }
}
