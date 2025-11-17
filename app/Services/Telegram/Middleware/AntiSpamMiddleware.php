<?php

namespace App\Services\Telegram\Middleware;

use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Closure;

/**
 * Middleware для защиты от спама одинаковыми сообщениями
 *
 * Блокирует пользователей которые:
 * - Отправляют одну и ту же команду подряд
 * - Отправляют одинаковый текст несколько раз
 */
class AntiSpamMiddleware
{
    public function __construct(
        private TelegramBotService $bot
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
        $text = $context['message']['text'] ?? null;

        if (!$userId || !$text) {
            return $next($context);
        }

        // Проверяем на спам
        if ($this->isSpam($userId, $text)) {
            $this->handleSpam($userId, $chatId, $text);
            return null;
        }

        // Сохраняем текущее сообщение
        $this->rememberMessage($userId, $text);

        return $next($context);
    }

    /**
     * Проверить является ли сообщение спамом
     */
    private function isSpam(string|int $userId, string $text): bool
    {
        $key = "telegram:last_messages:{$userId}";
        $lastMessages = Cache::get($key, []);

        // Если последние 3 сообщения одинаковые - это спам
        if (count($lastMessages) >= 3) {
            $unique = array_unique($lastMessages);
            if (count($unique) === 1 && $unique[0] === $text) {
                return true;
            }
        }

        return false;
    }

    /**
     * Запомнить сообщение
     */
    private function rememberMessage(string|int $userId, string $text): void
    {
        $key = "telegram:last_messages:{$userId}";
        $lastMessages = Cache::get($key, []);

        // Добавляем новое сообщение
        $lastMessages[] = $text;

        // Оставляем только последние 3
        if (count($lastMessages) > 3) {
            array_shift($lastMessages);
        }

        // Сохраняем на 5 минут
        Cache::put($key, $lastMessages, now()->addMinutes(5));
    }

    /**
     * Обработать спам
     */
    private function handleSpam(string|int $userId, int $chatId, string $text): void
    {
        Log::warning('Spam detected', [
            'user_id' => $userId,
            'text' => mb_substr($text, 0, 50),
        ]);

        // Блокируем пользователя на 5 минут
        $banKey = "telegram:spam_ban:{$userId}";
        $banCount = Cache::get($banKey, 0);

        if ($banCount >= 3) {
            // После 3 нарушений - бан на час
            Cache::put($banKey, $banCount + 1, now()->addHour());

            $this->bot->sendMessage(
                $chatId,
                "🚫 <b>Вы заблокированы за спам</b>\n\n" .
                "Срок блокировки: 1 час\n\n" .
                "Пожалуйста, прекратите отправлять одинаковые сообщения."
            );
        } else {
            Cache::put($banKey, $banCount + 1, now()->addMinutes(5));

            $this->bot->sendMessage(
                $chatId,
                "⚠️ <b>Предупреждение</b>\n\n" .
                "Не отправляйте одинаковые сообщения подряд.\n" .
                "При повторении вы будете заблокированы."
            );
        }
    }

    /**
     * Проверить заблокирован ли пользователь
     */
    public static function isBanned(string|int $userId): bool
    {
        $banKey = "telegram:spam_ban:{$userId}";
        $banCount = Cache::get($banKey, 0);
        return $banCount >= 3;
    }

    /**
     * Разблокировать пользователя
     */
    public static function unban(string|int $userId): void
    {
        Cache::forget("telegram:spam_ban:{$userId}");
        Cache::forget("telegram:last_messages:{$userId}");
    }
}
