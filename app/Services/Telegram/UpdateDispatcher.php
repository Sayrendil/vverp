<?php

namespace App\Services\Telegram;

use App\Services\Telegram\Handlers\UpdateHandler;
use Illuminate\Support\Facades\Log;

/**
 * Диспетчер обновлений Telegram
 *
 * Паттерн: Chain of Responsibility
 * Проходит по списку обработчиков и передаёт обновление первому,
 * который может его обработать
 */
class UpdateDispatcher
{
    /**
     * @var UpdateHandler[] Массив обработчиков
     */
    private array $handlers = [];

    /**
     * Зарегистрировать обработчик
     *
     * @param UpdateHandler $handler Обработчик
     * @return self
     */
    public function registerHandler(UpdateHandler $handler): self
    {
        $this->handlers[] = $handler;
        return $this;
    }

    /**
     * Диспетчеризация обновления к подходящему обработчику
     *
     * @param array $update Обновление от Telegram
     * @return bool True если обновление было обработано
     */
    public function dispatch(array $update): bool
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($update)) {
                try {
                    $handler->handle($update);
                    return true;
                } catch (\Exception $e) {
                    Log::error('Handler error: ' . get_class($handler), [
                        'error' => $e->getMessage(),
                        'update' => $update,
                    ]);
                    // Продолжаем искать другой обработчик
                }
            }
        }

        // Ни один обработчик не подошёл
        Log::warning('No handler found for update', [
            'update_id' => $update['update_id'] ?? null,
        ]);

        return false;
    }

    /**
     * Получить количество зарегистрированных обработчиков
     *
     * @return int
     */
    public function getHandlersCount(): int
    {
        return count($this->handlers);
    }
}
