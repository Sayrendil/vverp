<?php

namespace App\Console\Commands;

use App\Services\MonitoringService;
use Illuminate\Console\Command;

/**
 * Команда для проверки доступности всех активных хостов
 */
class MonitoringCheckHosts extends Command
{
    /**
     * Сигнатура команды
     *
     * @var string
     */
    protected $signature = 'monitoring:check-hosts
                            {--all : Проверить все хосты, игнорируя расписание}
                            {--store= : Проверить хосты конкретного магазина}
                            {--sync : Выполнить синхронно, без очереди}';

    /**
     * Описание команды
     *
     * @var string
     */
    protected $description = 'Проверить доступность хостов через ping';

    /**
     * Выполнить команду
     */
    public function handle(MonitoringService $monitoringService): int
    {
        $this->info('🔍 Запуск проверки доступности хостов...');

        $useQueue = !$this->option('sync');
        $checkAll = $this->option('all');
        $storeId = $this->option('store');

        try {
            if ($storeId) {
                // Проверка хостов конкретного магазина
                $this->info("Проверка хостов магазина ID: {$storeId}");
                $count = $monitoringService->checkStoreHosts((int)$storeId, $useQueue);
                $this->info("✅ Запущено проверок: {$count}");
            } elseif ($checkAll) {
                // Проверка всех активных хостов
                $this->info('Проверка всех активных хостов');
                $count = $monitoringService->checkAllActiveHosts($useQueue);
                $this->info("✅ Запущено проверок: {$count}");
            } else {
                // Проверка по расписанию (только те, которым пора)
                $this->info('Проверка хостов по расписанию');
                $count = $monitoringService->runScheduledChecks();

                if ($count > 0) {
                    $this->info("✅ Запущено проверок: {$count}");
                } else {
                    $this->info('ℹ️ Нет хостов для проверки в данный момент');
                }
            }

            if ($useQueue) {
                $this->comment('💡 Проверки добавлены в очередь "monitoring"');
                $this->comment('   Запустите: php artisan queue:work --queue=monitoring');
            } else {
                $this->comment('✅ Проверки выполнены синхронно');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Ошибка при проверке хостов: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
