<?php

namespace App\Services\Telegram\Middleware;

use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Closure;

/**
 * Middleware для ограничения частоты выполнения конкретных команд
 *
 * Более гибкий чем RateLimitMiddleware - можно настроить разные лимиты
 * для разных команд
 */
class CommandRateLimitMiddleware
{
    public function __construct(
        private TelegramBotService $bot,
        private int $maxAttempts = 5,
        private int $decayMinutes = 1
    ) {}

    /**
     * @param array $context
     * @param Closure $next
     * @return mixed
     */
    public function __invoke(array $context, Closure $next): mixed
    {
        $userId = $context['userId'] ?? null;
        $chatId = $context['chatId'] ?? null;
        $command = $this->extractCommand($context);

        if (!$userId || !$command) {
            return $next($context);
        }

        $key = "telegram:cmd_limit:{$userId}:{$command}";
        $attempts = Cache::get($key, 0);

        if ($attempts >= $this->maxAttempts) {
            $this->sendLimitMessage($chatId, $command);

            Log::warning('Command rate limit exceeded', [
                'user_id' => $userId,
                'command' => $command,
                'attempts' => $attempts,
            ]);

            return null;
        }

        // Увеличиваем счетчик
        Cache::put($key, $attempts + 1, now()->addMinutes($this->decayMinutes));

        return $next($context);
    }

    /**
     * Извлечь команду из контекста
     */
    private function extractCommand(array $context): ?string
    {
        $text = $context['message']['text'] ?? null;

        if (!$text || !str_starts_with($text, '/')) {
            return null;
        }

        return explode(' ', $text)[0];
    }

    /**
     * Отправить сообщение о лимите
     */
    private function sendLimitMessage(int $chatId, string $command): void
    {
        try {
            $this->bot->sendMessage(
                $chatId,
                "⏳ Вы слишком часто используете команду {$command}\n\n" .
                "Подождите {$this->decayMinutes} мин. и попробуйте снова."
            );
        } catch (\Exception $e) {
            Log::error('Failed to send command limit message', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
