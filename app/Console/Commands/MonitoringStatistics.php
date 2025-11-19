<?php

namespace App\Console\Commands;

use App\Services\MonitoringService;
use Illuminate\Console\Command;

/**
 * Команда для просмотра статистики мониторинга
 */
class MonitoringStatistics extends Command
{
    /**
     * Сигнатура команды
     *
     * @var string
     */
    protected $signature = 'monitoring:stats
                            {--days=7 : Количество дней для анализа}
                            {--host= : Статистика по конкретному хосту}';

    /**
     * Описание команды
     *
     * @var string
     */
    protected $description = 'Показать статистику мониторинга доступности';

    /**
     * Выполнить команду
     */
    public function handle(MonitoringService $monitoringService): int
    {
        $days = (int)$this->option('days');
        $hostId = $this->option('host');

        try {
            if ($hostId) {
                // Статистика по конкретному хосту
                $this->showHostStatistics($monitoringService, (int)$hostId, $days);
            } else {
                // Общая статистика
                $this->showOverallStatistics($monitoringService, $days);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Ошибка при получении статистики: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Показать общую статистику
     */
    private function showOverallStatistics(MonitoringService $monitoringService, int $days): void
    {
        $stats = $monitoringService->getOverallStatistics($days);

        $this->info("📊 Общая статистика мониторинга (за {$days} дней)");
        $this->newLine();

        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Всего хостов', $stats['total_hosts']],
                ['Активных', $stats['active_hosts']],
                ['Неактивных', $stats['inactive_hosts']],
                ['Всего проверок', $stats['total_checks']],
                ['Успешных проверок', $stats['successful_checks']],
                ['Неудачных проверок', $stats['failed_checks']],
                ['Uptime %', $stats['uptime_percent'] . '%'],
                ['Среднее время отклика', $stats['avg_response_time'] ? $stats['avg_response_time'] . ' мс' : 'N/A'],
                ['Проблемных хостов', $stats['problematic_hosts_count']],
            ]
        );

        if ($stats['problematic_hosts_count'] > 0) {
            $this->newLine();
            $this->warn("⚠️ Найдено проблемных хостов: {$stats['problematic_hosts_count']}");
            $this->comment('   Используйте: php artisan monitoring:problematic для деталей');
        }
    }

    /**
     * Показать статистику по хосту
     */
    private function showHostStatistics(MonitoringService $monitoringService, int $hostId, int $days): void
    {
        $stats = $monitoringService->getHostStatistics($hostId, $days);
        $host = $stats['host'];

        $this->info("📊 Статистика хоста: {$host->name} ({$host->ip_address})");
        $this->info("   Магазин: {$host->store->name}");
        $this->newLine();

        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Период', "{$days} дней"],
                ['Всего проверок', $stats['total_checks']],
                ['Доступен', $stats['available_checks']],
                ['Недоступен', $stats['unavailable_checks']],
                ['Uptime %', $stats['uptime_percent'] . '%'],
                ['Среднее время отклика', $stats['avg_response_time'] ? $stats['avg_response_time'] . ' мс' : 'N/A'],
                ['Мин. время отклика', $stats['min_response_time'] ? $stats['min_response_time'] . ' мс' : 'N/A'],
                ['Макс. время отклика', $stats['max_response_time'] ? $stats['max_response_time'] . ' мс' : 'N/A'],
                ['Средняя потеря пакетов', $stats['avg_packet_loss'] . '%'],
            ]
        );

        if ($stats['recent_logs']->isNotEmpty()) {
            $this->newLine();
            $this->info('🕐 Последние 10 проверок:');

            $recentData = $stats['recent_logs']->take(10)->map(function ($log) {
                return [
                    $log->checked_at->format('Y-m-d H:i:s'),
                    $log->is_available ? '✅ Доступен' : '❌ Недоступен',
                    $log->response_time ? $log->response_time . ' мс' : 'N/A',
                    $log->packet_loss . '%',
                ];
            })->toArray();

            $this->table(
                ['Время', 'Статус', 'Отклик', 'Потери'],
                $recentData
            );
        }
    }
}
