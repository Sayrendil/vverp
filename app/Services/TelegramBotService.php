<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с Telegram Bot API
 * Отвечает за отправку и получение сообщений через Telegram
 */
class TelegramBotService
{
    protected string $botToken;
    protected string $apiUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->botToken = config('telegram.bot_token');
        $this->apiUrl = config('telegram.api_url') . $this->botToken;
        $this->timeout = config('telegram.polling.timeout', 30);
    }

    /**
     * Получить обновления (Long Polling)
     *
     * @param int|null $offset Смещение для получения новых обновлений
     * @return array
     */
    public function getUpdates(?int $offset = null): array
    {
        try {
            $params = [
                'timeout' => $this->timeout,
                'limit' => config('telegram.polling.limit', 100),
                'allowed_updates' => json_encode(config('telegram.polling.allowed_updates')),
            ];

            if ($offset !== null) {
                $params['offset'] = $offset;
            }

            $response = Http::timeout($this->timeout + 5)
                ->get($this->apiUrl . '/getUpdates', $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['ok'] ?? false) {
                    $this->log('Получено обновлений: ' . count($data['result'] ?? []));
                    return $data['result'] ?? [];
                }
            }

            $this->log('Ошибка получения обновлений', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            $this->handleApiError($e, 'getUpdates');
            return [];
        }
    }

    /**
     * Отправить текстовое сообщение
     *
     * @param int|string $chatId ID чата
     * @param string $text Текст сообщения
     * @param array $options Дополнительные параметры (клавиатура, форматирование и т.д.)
     * @return array|null
     */
    public function sendMessage($chatId, string $text, array $options = []): ?array
    {
        try {
            $params = array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML', // Поддержка HTML форматирования
            ], $options);

            $response = Http::post($this->apiUrl . '/sendMessage', $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['ok'] ?? false) {
                    $this->log('Сообщение отправлено', [
                        'chat_id' => $chatId,
                        'message_id' => $data['result']['message_id'] ?? null,
                    ]);
                    return $data['result'] ?? null;
                }
            }

            $this->log('Ошибка отправки сообщения', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            $this->handleApiError($e, 'sendMessage');
            return null;
        }
    }

    /**
     * Отправить сообщение с кнопками (Inline Keyboard)
     *
     * @param int|string $chatId ID чата
     * @param string $text Текст сообщения
     * @param array $buttons Массив кнопок [[['text' => 'Кнопка', 'callback_data' => 'data']]]
     * @return array|null
     */
    public function sendMessageWithButtons($chatId, string $text, array $buttons): ?array
    {
        return $this->sendMessage($chatId, $text, [
            'reply_markup' => json_encode([
                'inline_keyboard' => $buttons,
            ]),
        ]);
    }

    /**
     * Ответить на callback query (нажатие на inline кнопку)
     *
     * @param string $callbackQueryId ID callback query
     * @param string|null $text Текст уведомления
     * @param bool $showAlert Показывать как alert
     * @return bool
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null, bool $showAlert = false): bool
    {
        try {
            $params = [
                'callback_query_id' => $callbackQueryId,
            ];

            if ($text !== null) {
                $params['text'] = $text;
                $params['show_alert'] = $showAlert;
            }

            $response = Http::post($this->apiUrl . '/answerCallbackQuery', $params);

            if ($response->successful()) {
                $data = $response->json();
                return $data['ok'] ?? false;
            }

            return false;
        } catch (\Exception $e) {
            $this->handleApiError($e, 'answerCallbackQuery');
            return false;
        }
    }

    /**
     * Отредактировать сообщение
     *
     * @param int|string $chatId ID чата
     * @param int $messageId ID сообщения
     * @param string $text Новый текст
     * @param array $options Дополнительные параметры
     * @return array|null
     */
    public function editMessage($chatId, int $messageId, string $text, array $options = []): ?array
    {
        try {
            $params = array_merge([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ], $options);

            $response = Http::post($this->apiUrl . '/editMessageText', $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['ok'] ?? false) {
                    return $data['result'] ?? null;
                }
            }

            return null;
        } catch (\Exception $e) {
            $this->handleApiError($e, 'editMessageText');
            return null;
        }
    }

    /**
     * Отредактировать сообщение с кнопками (Inline Keyboard)
     *
     * @param int|string $chatId ID чата
     * @param int $messageId ID сообщения
     * @param string $text Новый текст
     * @param array $buttons Массив кнопок
     * @return array|null
     */
    public function editMessageWithButtons($chatId, int $messageId, string $text, array $buttons): ?array
    {
        return $this->editMessage($chatId, $messageId, $text, [
            'reply_markup' => json_encode([
                'inline_keyboard' => $buttons,
            ]),
        ]);
    }

    /**
     * Удалить сообщение
     *
     * @param int|string $chatId ID чата
     * @param int $messageId ID сообщения
     * @return bool
     */
    public function deleteMessage($chatId, int $messageId): bool
    {
        try {
            $response = Http::post($this->apiUrl . '/deleteMessage', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['ok'] ?? false;
            }

            return false;
        } catch (\Exception $e) {
            $this->handleApiError($e, 'deleteMessage');
            return false;
        }
    }

    /**
     * Получить информацию о пользователе через getChatMember
     *
     * @param int $userId ID пользователя Telegram
     * @param int|string $chatId ID чата (можно использовать userId как chatId для личных сообщений)
     * @return array|null
     */
    public function getUserInfo(int $userId, $chatId = null): ?array
    {
        try {
            $chatId = $chatId ?? $userId;

            $response = Http::get($this->apiUrl . '/getChat', [
                'chat_id' => $chatId,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['ok'] ?? false) {
                    return $data['result'] ?? null;
                }
            }

            return null;
        } catch (\Exception $e) {
            $this->handleApiError($e, 'getUserInfo');
            return null;
        }
    }

    /**
     * Получить информацию о боте
     *
     * @return array|null
     */
    public function getMe(): ?array
    {
        try {
            $response = Http::get($this->apiUrl . '/getMe');

            if ($response->successful()) {
                $data = $response->json();

                if ($data['ok'] ?? false) {
                    $this->log('Информация о боте получена', [
                        'username' => $data['result']['username'] ?? null,
                        'first_name' => $data['result']['first_name'] ?? null,
                    ]);
                    return $data['result'] ?? null;
                }
            }

            $this->log('Ошибка получения информации о боте', [
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            $this->handleApiError($e, 'getMe');
            return null;
        }
    }

    /**
     * Получить информацию о файле
     *
     * @param string $fileId ID файла
     * @return array|null
     */
    public function getFile(string $fileId): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->apiUrl}/getFile", [
                    'file_id' => $fileId,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['result'] ?? null;
            }

            $this->log('getFile failed', [
                'file_id' => $fileId,
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            $this->handleApiError($e, 'getFile');
            return null;
        }
    }

    /**
     * Скачать файл из Telegram
     *
     * @param string $fileId ID файла
     * @param string $savePath Путь для сохранения (относительно storage/app/public)
     * @return string|null Путь к сохраненному файлу
     */
    public function downloadFile(string $fileId, string $savePath): ?string
    {
        try {
            // Получаем информацию о файле
            $fileInfo = $this->getFile($fileId);

            if (!$fileInfo || !isset($fileInfo['file_path'])) {
                return null;
            }

            // URL для скачивания
            $fileUrl = "https://api.telegram.org/file/bot{$this->botToken}/{$fileInfo['file_path']}";

            // Скачиваем файл
            $fileContent = Http::timeout(60)->get($fileUrl)->body();

            // Сохраняем файл
            $fullPath = storage_path("app/public/{$savePath}");
            $directory = dirname($fullPath);

            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($fullPath, $fileContent);

            return $savePath;
        } catch (\Exception $e) {
            $this->handleApiError($e, 'downloadFile');
            return null;
        }
    }

    /**
     * Логирование активности бота
     *
     * @param string $message Сообщение для лога
     * @param array $context Контекст
     * @return void
     */
    protected function log(string $message, array $context = []): void
    {
        if (config('telegram.logging.enabled')) {
            Log::channel(config('telegram.logging.channel'))->info($message, $context);
        }
    }

    /**
     * Обработка ошибок API
     *
     * @param \Exception $e Исключение
     * @param string $method Метод API
     * @return void
     */
    protected function handleApiError(\Exception $e, string $method): void
    {
        Log::error("Telegram API Error [{$method}]: " . $e->getMessage(), [
            'exception' => $e,
        ]);
    }
}
