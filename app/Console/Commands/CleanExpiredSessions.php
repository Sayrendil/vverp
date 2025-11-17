<?php

namespace App\Console\Commands;

use App\Models\TelegramSession;
use Illuminate\Console\Command;

/**
 * Команда для очистки устаревших Telegram сессий
 *
 * Удаляет сессии у которых expires_at меньше текущего времени.
 * Рекомендуется запускать через Schedule каждый час.
 */
class CleanExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:clean-sessions
                            {--force : Удалить без подтверждения}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистка устаревших Telegram сессий';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Поиск устаревших сессий...');

        // Находим устаревшие сессии
        $expiredSessions = TelegramSession::query()
            ->where('expires_at', '<', now())
            ->get();

        $count = $expiredSessions->count();

        if ($count === 0) {
            $this->info('✅ Устаревших сессий не найдено');
            return Command::SUCCESS;
        }

        $this->warn("Найдено устаревших сессий: {$count}");

        // Показываем детали если сессий немного
        if ($count <= 10) {
            $this->table(
                ['ID', 'User ID', 'Telegram ID', 'Шаг', 'Истекла'],
                $expiredSessions->map(fn($s) => [
                    $s->id,
                    $s->user_id,
                    $s->telegram_user_id,
                    $s->step->value,
                    $s->expires_at->diffForHumans(),
                ])->toArray()
            );
        }

        // Если не force - запрашиваем подтверждение
        if (!$this->option('force') && !$this->confirm("Удалить {$count} сессий?")) {
            $this->info('❌ Операция отменена');
            return Command::SUCCESS;
        }

        // Удаляем
        $deleted = TelegramSession::query()
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("✅ Удалено сессий: {$deleted}");

        return Command::SUCCESS;
    }
}
