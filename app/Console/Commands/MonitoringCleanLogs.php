<?php

namespace App\Console\Commands;

use App\Services\MonitoringService;
use Illuminate\Console\Command;

/**
 * Команда для очистки старых логов мониторинга
 */
class MonitoringCleanLogs extends Command
{
    /**
     * Сигнатура команды
     *
     * @var string
     */
    protected $signature = 'monitoring:clean-logs
                            {--days=30 : Удалить логи старше указанного количества дней}';

    /**
     * Описание команды
     *
     * @var string
     */
    protected $description = 'Очистить старые логи мониторинга';

    /**
     * Выполнить команду
     */
    public function handle(MonitoringService $monitoringService): int
    {
        $days = (int)$this->option('days');

        if (!$this->confirm("Удалить логи старше {$days} дней?", true)) {
            $this->info('❌ Операция отменена');
            return Command::SUCCESS;
        }

        try {
            $this->info("🗑️ Очистка логов старше {$days} дней...");

            $deleted = $monitoringService->cleanOldLogs($days);

            $this->info("✅ Удалено записей: {$deleted}");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Ошибка при очистке логов: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
