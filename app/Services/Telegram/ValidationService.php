<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Сервис для валидации данных от Telegram бота
 */
class ValidationService
{
    /**
     * Валидация текста сообщения
     *
     * @param string $text Текст для валидации
     * @param int $minLength Минимальная длина
     * @param int $maxLength Максимальная длина (лимит Telegram - 4096)
     * @return bool
     * @throws ValidationException
     */
    public function validateText(string $text, int $minLength = 1, int $maxLength = 4096): bool
    {
        $validator = Validator::make(
            ['text' => $text],
            [
                'text' => [
                    'required',
                    'string',
                    "min:{$minLength}",
                    "max:{$maxLength}",
                ],
            ],
            [
                'text.required' => 'Текст не может быть пустым',
                'text.min' => "Текст должен содержать минимум {$minLength} символов",
                'text.max' => "Текст не должен превышать {$maxLength} символов (лимит Telegram)",
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return true;
    }

    /**
     * Валидация описания тикета
     *
     * @param string $description Описание
     * @return array{valid: bool, error: string|null}
     */
    public function validateDescription(string $description): array
    {
        // Минимальная длина
        if (mb_strlen($description) < 20) {
            return [
                'valid' => false,
                'error' => '❌ Описание слишком короткое. Минимум 20 символов.',
            ];
        }

        // Максимальная длина (лимит Telegram)
        if (mb_strlen($description) > 4096) {
            return [
                'valid' => false,
                'error' => '❌ Описание слишком длинное. Максимум 4096 символов.',
            ];
        }

        // Проверка на спам (только повторяющиеся символы)
        if (preg_match('/^(.)\1{19,}$/', $description)) {
            return [
                'valid' => false,
                'error' => '❌ Описание выглядит как спам. Напишите осмысленный текст.',
            ];
        }

        // Проверка на недопустимые символы (например, управляющие)
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $description)) {
            return [
                'valid' => false,
                'error' => '❌ Описание содержит недопустимые символы.',
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Валидация ID сущности
     *
     * @param mixed $id ID для проверки
     * @param string $entityName Название сущности (для сообщения об ошибке)
     * @return array{valid: bool, error: string|null}
     */
    public function validateId($id, string $entityName = 'Объект'): array
    {
        if (!is_numeric($id) || $id <= 0) {
            return [
                'valid' => false,
                'error' => "❌ Некорректный ID {$entityName}",
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Валидация Telegram Chat ID
     *
     * @param mixed $chatId Chat ID
     * @return bool
     */
    public function validateChatId($chatId): bool
    {
        // Chat ID может быть числом (для личных чатов) или строкой (для каналов)
        if (is_int($chatId)) {
            return true;
        }

        if (is_string($chatId) && preg_match('/^@[\w]{5,}$/', $chatId)) {
            return true;
        }

        return false;
    }

    /**
     * Валидация количества вложений
     *
     * @param array $attachments Массив вложений
     * @param int $maxCount Максимальное количество
     * @return array{valid: bool, error: string|null}
     */
    public function validateAttachments(array $attachments, int $maxCount = 10): array
    {
        if (count($attachments) > $maxCount) {
            return [
                'valid' => false,
                'error' => "❌ Максимум {$maxCount} файлов. У вас: " . count($attachments),
            ];
        }

        // Проверяем структуру каждого вложения
        foreach ($attachments as $index => $attachment) {
            if (!isset($attachment['file_id']) || !isset($attachment['type'])) {
                return [
                    'valid' => false,
                    'error' => "❌ Некорректная структура файла #{$index}",
                ];
            }

            if (!in_array($attachment['type'], ['photo', 'video', 'document'])) {
                return [
                    'valid' => false,
                    'error' => "❌ Неподдерживаемый тип файла: {$attachment['type']}",
                ];
            }
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Санитизация HTML для безопасного вывода в Telegram
     *
     * @param string $text Текст
     * @return string
     */
    public function sanitizeHtml(string $text): string
    {
        // Telegram поддерживает ограниченный набор HTML тегов
        $allowedTags = '<b><strong><i><em><u><ins><s><strike><del><code><pre><a>';

        // Удаляем все теги кроме разрешенных
        $text = strip_tags($text, $allowedTags);

        // Экранируем специальные HTML символы в тексте (но не в тегах)
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

        return $text;
    }

    /**
     * Валидация данных сессии для создания тикета
     *
     * @param array $sessionData Данные из сессии
     * @return array{valid: bool, errors: array}
     */
    public function validateSessionData(array $sessionData): array
    {
        $errors = [];

        // Проверяем обязательные поля
        if (empty($sessionData['description'])) {
            $errors[] = 'Отсутствует описание';
        } else {
            $result = $this->validateDescription($sessionData['description']);
            if (!$result['valid']) {
                $errors[] = $result['error'];
            }
        }

        if (empty($sessionData['problem_id'])) {
            $errors[] = 'Не выбрана проблема';
        }

        // Проверяем вложения если есть
        if (!empty($sessionData['attachments'])) {
            $result = $this->validateAttachments($sessionData['attachments']);
            if (!$result['valid']) {
                $errors[] = $result['error'];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
