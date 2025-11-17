<?php

namespace App\Services\Telegram\Handlers;

/**
 * Базовый интерфейс для обработчиков обновлений Telegram
 *
 * Паттерн: Chain of Responsibility
 * Каждый обработчик проверяет, может ли он обработать обновление
 * Если да - обрабатывает, если нет - передает следующему
 */
interface UpdateHandler
{
    /**
     * Проверить, может ли обработчик обработать это обновление
     *
     * @param array $update Обновление от Telegram
     * @return bool
     */
    public function supports(array $update): bool;

    /**
     * Обработать обновление
     *
     * @param array $update Обновление от Telegram
     * @return void
     */
    public function handle(array $update): void;
}
