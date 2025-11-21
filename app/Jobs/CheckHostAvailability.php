<?php

namespace App\Jobs;

use App\Models\Host;
use App\Models\HostAvailabilityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job для проверки доступности хоста через ping
 *
 * Выполняет проверку и записывает результат в базу данных
 */
class CheckHostAvailability implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Количество попыток выполнения задачи
     */
    public int $tries = 3;

    /**
     * Таймаут выполнения (секунды)
     */
    public int $timeout = 30;

    /**
     * Задержка перед повторными попытками (секунды)
     * Увеличенные интервалы для лучшей обработки сетевых проблем
     */
    public array $backoff = [10, 30, 60];

    /**
     * @param int $hostId ID хоста для проверки
     */
    public function __construct(
        private int $hostId
    ) {
        // Используем отдельную очередь для мониторинга
        $this->onQueue('monitoring');
    }

    /**
     * Уникальный ID задачи для предотвращения дублирования
     * Если задача с таким ID уже в очереди, новая не будет добавлена
     */
    public function uniqueId(): string
    {
        return 'check-host-' . $this->hostId;
    }

    /**
     * Выполнить проверку доступности хоста
     */
    public function handle(): void
    {
        try {
            $host = Host::find($this->hostId);

            if (!$host) {
                Log::warning("Host not found for availability check", ['host_id' => $this->hostId]);
                return;
            }

            if (!$host->is_active) {
                Log::debug("Host is inactive, skipping check", ['host_id' => $this->hostId]);
                return;
            }

            Log::debug("Checking host availability", [
                'host_id' => $host->id,
                'host_name' => $host->name,
                'ip_address' => $host->ip_address,
            ]);

            // Выполняем ping
            $result = $this->pingHost($host->ip_address, $host->timeout);

            // Сохраняем результат в базу данных
            HostAvailabilityLog::create([
                'host_id' => $host->id,
                'is_available' => $result['is_available'],
                'response_time' => $result['response_time'],
                'packet_loss' => $result['packet_loss'],
                'error_message' => $result['error_message'],
                'checked_at' => now(),
            ]);

            // Логируем только проблемы или важные события
            if (!$result['is_available']) {
                Log::warning("Host is unavailable", [
                    'host_id' => $host->id,
                    'host_name' => $host->name,
                    'ip_address' => $host->ip_address,
                    'error_message' => $result['error_message'],
                ]);
            } else {
                Log::debug("Host availability check completed", [
                    'host_id' => $host->id,
                    'is_available' => $result['is_available'],
                    'response_time' => $result['response_time'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Error checking host availability", [
                'host_id' => $this->hostId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Записываем ошибку в логи
            if ($host ?? null) {
                HostAvailabilityLog::create([
                    'host_id' => $host->id,
                    'is_available' => false,
                    'response_time' => null,
                    'packet_loss' => 100,
                    'error_message' => 'Job error: ' . $e->getMessage(),
                    'checked_at' => now(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Выполнить ping хоста
     *
     * @param string $ipAddress IP адрес или hostname
     * @param int $timeout Таймаут в секундах
     * @return array
     */
    private function pingHost(string $ipAddress, int $timeout = 3): array
    {
        $startTime = microtime(true);
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        try {
            // Проверяем доступность команды ping
            $pingCommand = $isWindows ? 'ping' : 'ping';
            $checkCommand = $isWindows ? 'where ping' : 'which ping';
            exec($checkCommand, $checkOutput, $checkReturn);

            if ($checkReturn !== 0) {
                throw new \Exception('Ping command is not available on this system');
            }

            // Формируем команду ping в зависимости от ОС
            if ($isWindows) {
                // Windows: ping -n 4 -w timeout_in_ms ip
                $timeoutMs = $timeout * 1000;
                $command = sprintf('ping -n 4 -w %d %s', $timeoutMs, escapeshellarg($ipAddress));
            } else {
                // Linux/Unix: ping -c 4 -W timeout ip
                $command = sprintf('ping -c 4 -W %d %s', $timeout, escapeshellarg($ipAddress));
            }

            // Выполняем команду
            exec($command, $output, $returnCode);
            $executionTime = (microtime(true) - $startTime) * 1000; // в миллисекундах

            // Анализируем вывод
            $outputString = implode("\n", $output);

            // Проверяем доступность
            $isAvailable = $returnCode === 0;

            // Извлекаем время отклика и потери пакетов
            $responseTime = null;
            $packetLoss = 0;

            if ($isWindows) {
                // Пример: "Среднее = 2ms" или "Average = 2ms"
                if (preg_match('/Average = (\d+)ms/i', $outputString, $matches) ||
                    preg_match('/Среднее = (\d+)мс/iu', $outputString, $matches)) {
                    $responseTime = (int)$matches[1];
                }

                // Пример: "(25% потерь)" или "(25% loss)"
                if (preg_match('/\((\d+)% loss\)/i', $outputString, $matches) ||
                    preg_match('/\((\d+)% потерь\)/iu', $outputString, $matches)) {
                    $packetLoss = (int)$matches[1];
                }
            } else {
                // Linux: "rtt min/avg/max/mdev = 0.043/0.046/0.048/0.002 ms"
                if (preg_match('/rtt min\/avg\/max\/mdev = [\d.]+\/([\d.]+)\/[\d.]+\/[\d.]+ ms/i', $outputString, $matches)) {
                    $responseTime = (int)round(floatval($matches[1]));
                }

                // Linux: "4 packets transmitted, 3 received, 25% packet loss"
                if (preg_match('/(\d+)% packet loss/i', $outputString, $matches)) {
                    $packetLoss = (int)$matches[1];
                }
            }

            // Если время отклика не извлечено, используем общее время выполнения
            if ($responseTime === null && $isAvailable) {
                $responseTime = (int)round($executionTime / 4); // делим на количество пакетов
            }

            return [
                'is_available' => $isAvailable,
                'response_time' => $responseTime,
                'packet_loss' => $packetLoss,
                'error_message' => $isAvailable ? null : 'Host unreachable or timeout',
            ];

        } catch (\Exception $e) {
            return [
                'is_available' => false,
                'response_time' => null,
                'packet_loss' => 100,
                'error_message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Обработка провала задачи
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("CheckHostAvailability job failed", [
            'host_id' => $this->hostId,
            'error' => $exception->getMessage(),
        ]);
    }
}
