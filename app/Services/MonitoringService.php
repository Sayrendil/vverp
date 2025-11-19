<?php

namespace App\Services;

use App\Jobs\CheckHostAvailability;
use App\Models\Host;
use App\Models\HostAvailabilityLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для управления мониторингом доступности хостов
 *
 * Централизованная бизнес-логика для системы мониторинга
 */
class MonitoringService
{
    /**
     * Проверить все активные хосты
     *
     * @param bool $useQueue Использовать очередь (true) или выполнить синхронно (false)
     * @return int Количество запущенных проверок
     */
    public function checkAllActiveHosts(bool $useQueue = true): int
    {
        $hosts = Host::active()->get();
        $count = 0;

        foreach ($hosts as $host) {
            $this->checkHost($host->id, $useQueue);
            $count++;
        }

        Log::info("Scheduled monitoring checks", [
            'total_hosts' => $count,
            'use_queue' => $useQueue,
        ]);

        return $count;
    }

    /**
     * Проверить конкретный хост
     *
     * @param int $hostId ID хоста
     * @param bool $useQueue Использовать очередь (true) или выполнить синхронно (false)
     * @return void
     */
    public function checkHost(int $hostId, bool $useQueue = true): void
    {
        if ($useQueue) {
            // Асинхронная проверка через очередь
            CheckHostAvailability::dispatch($hostId);
        } else {
            // Синхронная проверка (для тестирования или manual check)
            CheckHostAvailability::dispatchSync($hostId);
        }
    }

    /**
     * Проверить хосты конкретного магазина
     *
     * @param int $storeId ID магазина
     * @param bool $useQueue Использовать очередь
     * @return int Количество запущенных проверок
     */
    public function checkStoreHosts(int $storeId, bool $useQueue = true): int
    {
        $hosts = Host::active()->forStore($storeId)->get();
        $count = 0;

        foreach ($hosts as $host) {
            $this->checkHost($host->id, $useQueue);
            $count++;
        }

        return $count;
    }

    /**
     * Получить статистику по всем хостам
     *
     * @param int $days Количество дней для анализа
     * @return array
     */
    public function getOverallStatistics(int $days = 7): array
    {
        $totalHosts = Host::count();
        $activeHosts = Host::active()->count();
        $inactiveHosts = $totalHosts - $activeHosts;

        $dateFrom = now()->subDays($days);

        // Получаем статистику по доступности
        $totalChecks = HostAvailabilityLog::where('checked_at', '>=', $dateFrom)->count();
        $successfulChecks = HostAvailabilityLog::where('checked_at', '>=', $dateFrom)
            ->where('is_available', true)
            ->count();
        $failedChecks = $totalChecks - $successfulChecks;

        // Средний uptime
        $uptimePercent = $totalChecks > 0 ? round(($successfulChecks / $totalChecks) * 100, 2) : 0;

        // Среднее время отклика
        $avgResponseTime = HostAvailabilityLog::where('checked_at', '>=', $dateFrom)
            ->where('is_available', true)
            ->avg('response_time');

        // Хосты с проблемами (последние 10 проверок показали недоступность)
        $problematicHosts = $this->getProblematicHosts(10);

        return [
            'period_days' => $days,
            'total_hosts' => $totalHosts,
            'active_hosts' => $activeHosts,
            'inactive_hosts' => $inactiveHosts,
            'total_checks' => $totalChecks,
            'successful_checks' => $successfulChecks,
            'failed_checks' => $failedChecks,
            'uptime_percent' => $uptimePercent,
            'avg_response_time' => $avgResponseTime ? round($avgResponseTime, 2) : null,
            'problematic_hosts_count' => count($problematicHosts),
        ];
    }

    /**
     * Получить список проблемных хостов
     *
     * @param int $checksCount Количество последних проверок для анализа
     * @return Collection
     */
    public function getProblematicHosts(int $checksCount = 10): Collection
    {
        return Host::active()
            ->with(['lastAvailabilityLog', 'store'])
            ->get()
            ->filter(function ($host) use ($checksCount) {
                $recentLogs = $host->availabilityLogs()
                    ->orderBy('checked_at', 'desc')
                    ->limit($checksCount)
                    ->get();

                if ($recentLogs->isEmpty()) {
                    return false;
                }

                // Считаем процент недоступности
                $unavailableCount = $recentLogs->where('is_available', false)->count();
                $unavailablePercent = ($unavailableCount / $recentLogs->count()) * 100;

                // Помечаем как проблемный, если более 50% проверок неуспешны
                return $unavailablePercent > 50;
            })
            ->values();
    }

    /**
     * Получить детальную статистику по хосту
     *
     * @param int $hostId ID хоста
     * @param int $days Количество дней для анализа
     * @return array
     */
    public function getHostStatistics(int $hostId, int $days = 7): array
    {
        $host = Host::with('store')->findOrFail($hostId);
        $dateFrom = now()->subDays($days);

        $logs = HostAvailabilityLog::where('host_id', $hostId)
            ->where('checked_at', '>=', $dateFrom)
            ->orderBy('checked_at', 'desc')
            ->get();

        $totalChecks = $logs->count();
        $availableChecks = $logs->where('is_available', true)->count();
        $unavailableChecks = $totalChecks - $availableChecks;

        $uptimePercent = $totalChecks > 0 ? round(($availableChecks / $totalChecks) * 100, 2) : 0;

        $avgResponseTime = $logs->where('is_available', true)->avg('response_time');
        $minResponseTime = $logs->where('is_available', true)->min('response_time');
        $maxResponseTime = $logs->where('is_available', true)->max('response_time');

        $avgPacketLoss = $logs->avg('packet_loss');

        // Последние проверки для графика
        $recentLogs = $logs->take(100)->reverse()->values();

        // Группировка по часам для графика
        $hourlyStats = $logs->groupBy(function ($log) {
            return $log->checked_at->format('Y-m-d H:00');
        })->map(function ($hourLogs) {
            $total = $hourLogs->count();
            $available = $hourLogs->where('is_available', true)->count();
            $avgResponse = $hourLogs->where('is_available', true)->avg('response_time');

            return [
                'total' => $total,
                'available' => $available,
                'unavailable' => $total - $available,
                'uptime_percent' => $total > 0 ? round(($available / $total) * 100, 2) : 0,
                'avg_response_time' => $avgResponse ? round($avgResponse, 2) : null,
            ];
        });

        return [
            'host' => $host,
            'period_days' => $days,
            'total_checks' => $totalChecks,
            'available_checks' => $availableChecks,
            'unavailable_checks' => $unavailableChecks,
            'uptime_percent' => $uptimePercent,
            'avg_response_time' => $avgResponseTime ? round($avgResponseTime, 2) : null,
            'min_response_time' => $minResponseTime,
            'max_response_time' => $maxResponseTime,
            'avg_packet_loss' => $avgPacketLoss ? round($avgPacketLoss, 2) : 0,
            'recent_logs' => $recentLogs,
            'hourly_stats' => $hourlyStats,
        ];
    }

    /**
     * Очистить старые логи
     *
     * @param int $days Удалить логи старше указанного количества дней
     * @return int Количество удаленных записей
     */
    public function cleanOldLogs(int $days = 30): int
    {
        $dateFrom = now()->subDays($days);

        $deleted = HostAvailabilityLog::where('checked_at', '<', $dateFrom)->delete();

        Log::info("Cleaned old monitoring logs", [
            'days' => $days,
            'deleted_count' => $deleted,
        ]);

        return $deleted;
    }

    /**
     * Получить хосты, которые нужно проверить (по расписанию)
     *
     * @return Collection
     */
    public function getHostsToCheck(): Collection
    {
        return Host::active()
            ->with('lastAvailabilityLog')
            ->get()
            ->filter(function ($host) {
                // Если хост никогда не проверялся - проверяем
                if (!$host->lastAvailabilityLog) {
                    return true;
                }

                // Проверяем, прошел ли интервал с последней проверки
                $lastCheck = $host->lastAvailabilityLog->checked_at;
                $intervalMinutes = $host->check_interval;
                $nextCheckTime = $lastCheck->addMinutes($intervalMinutes);

                return now()->greaterThanOrEqualTo($nextCheckTime);
            });
    }

    /**
     * Запустить проверки по расписанию
     *
     * @return int Количество запущенных проверок
     */
    public function runScheduledChecks(): int
    {
        $hostsToCheck = $this->getHostsToCheck();
        $count = 0;

        foreach ($hostsToCheck as $host) {
            $this->checkHost($host->id, true);
            $count++;
        }

        if ($count > 0) {
            Log::info("Scheduled monitoring checks dispatched", [
                'hosts_checked' => $count,
            ]);
        }

        return $count;
    }
}
