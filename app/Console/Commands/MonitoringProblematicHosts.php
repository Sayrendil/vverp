<?php

namespace App\Console\Commands;

use App\Services\MonitoringService;
use Illuminate\Console\Command;

/**
 * Команда для просмотра списка проблемных хостов
 */
class MonitoringProblematicHosts extends Command
{
    /**
     * Сигнатура команды
     *
     * @var string
     */
    protected $signature = 'monitoring:problematic
                            {--checks=10 : Количество последних проверок для анализа}';

    /**
     * Описание команды
     *
     * @var string
     */
    protected $description = 'Показать список проблемных хостов';

    /**
     * Выполнить команду
     */
    public function handle(MonitoringService $monitoringService): int
    {
        $checksCount = (int)$this->option('checks');

        try {
            $this->info("⚠️ Поиск проблемных хостов (анализ последних {$checksCount} проверок)...");
            $this->newLine();

            $problematicHosts = $monitoringService->getProblematicHosts($checksCount);

            if ($problematicHosts->isEmpty()) {
                $this->info('✅ Проблемных хостов не найдено!');
                return Command::SUCCESS;
            }

            $this->warn("Найдено проблемных хостов: {$problematicHosts->count()}");
            $this->newLine();

            $tableData = $problematicHosts->map(function ($host) use ($checksCount) {
                $recentLogs = $host->availabilityLogs()
                    ->orderBy('checked_at', 'desc')
                    ->limit($checksCount)
                    ->get();

                $unavailableCount = $recentLogs->where('is_available', false)->count();
                $unavailablePercent = round(($unavailableCount / $recentLogs->count()) * 100, 1);

                $lastCheck = $host->lastAvailabilityLog;
                $lastStatus = $lastCheck ? ($lastCheck->is_available ? '✅' : '❌') : '?';
                $lastCheckTime = $lastCheck ? $lastCheck->checked_at->diffForHumans() : 'Никогда';

                return [
                    $host->id,
                    $host->store->name,
                    $host->name,
                    $host->ip_address,
                    "{$unavailablePercent}%",
                    "{$lastStatus} {$lastCheckTime}",
                ];
            })->toArray();

            $this->table(
                ['ID', 'Магазин', 'Хост', 'IP', 'Недоступность', 'Последняя проверка'],
                $tableData
            );

            $this->newLine();
            $this->comment('💡 Для детальной статистики: php artisan monitoring:stats --host=ID');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Ошибка при поиске проблемных хостов: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
