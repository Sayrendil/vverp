<?php

namespace App\Services\Telegram\Middleware;

use App\Services\TelegramBotService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Closure;

/**
 * Middleware для ограничения частоты запросов к боту
 *
 * Защищает от:
 * - Спама пользователями
 * - DDoS атак
 * - Превышения лимитов Telegram API
 */
class RateLimitMiddleware
{
    public function __construct(
        private TelegramBotService $bot
    ) {}

    /**
     * Обработать запрос
     *
     * @param array $context Контекст (userId, chatId, username)
     * @param Closure $next Следующий в цепочке
     * @return mixed
     */
    public function __invoke(array $context, Closure $next): mixed
    {
        $userId = $context['userId'] ?? null;
        $chatId = $context['chatId'] ?? null;

        if (!$userId) {
            return $next($context);
        }

        // Проверяем лимит
        if (!$this->checkRateLimit($userId)) {
            $this->sendRateLimitMessage($chatId);

            Log::warning('Rate limit exceeded', [
                'user_id' => $userId,
                'username' => $context['username'] ?? 'unknown',
            ]);

            return null; // Блокируем выполнение
        }

        return $next($context);
    }

    /**
     * Проверить лимит запросов
     *
     * @param string|int $userId
     * @return bool True если лимит не превышен
     */
    private function checkRateLimit(string|int $userId): bool
    {
        $key = "telegram:ratelimit:user:{$userId}";

        // Лимит: 10 запросов в минуту
        return RateLimiter::attempt(
            $key,
            $perMinute = 10,
            function() {
                // Разрешаем выполнение
            },
            $decaySeconds = 60
        );
    }

    /**
     * Отправить сообщение о превышении лимита
     */
    private function sendRateLimitMessage(int $chatId): void
    {
        try {
            $this->bot->sendMessage(
                $chatId,
                "⏳ <b>Слишком много запросов</b>\n\n" .
                "Вы превысили лимит запросов.\n" .
                "Пожалуйста, подождите минуту и попробуйте снова."
            );
        } catch (\Exception $e) {
            // Игнорируем ошибки при отправке (возможно сам лимит API)
            Log::error('Failed to send rate limit message', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Получить оставшиеся попытки
     *
     * @param string|int $userId
     * @return int
     */
    public static function remaining(string|int $userId): int
    {
        $key = "telegram:ratelimit:user:{$userId}";
        return RateLimiter::remaining($key, 10);
    }

    /**
     * Очистить лимит для пользователя (для админов)
     *
     * @param string|int $userId
     * @return void
     */
    public static function clear(string|int $userId): void
    {
        $key = "telegram:ratelimit:user:{$userId}";
        RateLimiter::clear($key);
    }
}
