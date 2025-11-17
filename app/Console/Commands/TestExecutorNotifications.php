<?php

namespace App\Console\Commands;

use App\Events\TicketCreated;
use App\Models\Ticket;
use Illuminate\Console\Command;

/**
 * Команда для тестирования уведомлений исполнителям
 *
 * Использование: php artisan test:executor-notifications {ticket_id}
 */
class TestExecutorNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:executor-notifications {ticket_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирование отправки уведомлений исполнителям о заявке';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $ticketId = $this->argument('ticket_id');

        if (!$ticketId) {
            // Если не указан ID, берем последнюю заявку
            $ticket = Ticket::with([
                'ticketCategory.executors' => function ($query) {
                    $query->whereNotNull('telegram_id')
                          ->wherePivot('is_active', true);
                }
            ])->latest()->first();

            if (!$ticket) {
                $this->error('❌ Заявки не найдены в системе');
                return 1;
            }

            $this->info("ℹ️  ID заявки не указан, используем последнюю: #{$ticket->id}");
        } else {
            $ticket = Ticket::with([
                'ticketCategory.executors' => function ($query) {
                    $query->whereNotNull('telegram_id')
                          ->wherePivot('is_active', true);
                }
            ])->find($ticketId);

            if (!$ticket) {
                $this->error("❌ Заявка #{$ticketId} не найдена");
                return 1;
            }
        }

        // Проверяем категорию
        if (!$ticket->ticket_category_id) {
            $this->error("❌ У заявки #{$ticket->id} не указана категория");
            return 1;
        }

        $category = $ticket->ticketCategory;
        $this->info("📁 Категория: {$category->name}");

        // Проверяем исполнителей
        $executors = $category->activeExecutors()->whereNotNull('telegram_id')->get();

        if ($executors->isEmpty()) {
            $this->warn("⚠️  В категории '{$category->name}' нет активных исполнителей с telegram_id");
            $this->info("\nВсе исполнители категории:");

            $allExecutors = $category->executors;
            if ($allExecutors->isEmpty()) {
                $this->line("  - (нет исполнителей)");
            } else {
                foreach ($allExecutors as $executor) {
                    $active = $executor->pivot->is_active ? '✅' : '❌';
                    $tg = $executor->telegram_id ? "TG: {$executor->telegram_id}" : "TG: не указан";
                    $this->line("  {$active} {$executor->name} ({$tg})");
                }
            }

            return 1;
        }

        // Показываем кому будут отправлены уведомления
        $this->info("\n📤 Уведомления будут отправлены:");
        foreach ($executors as $executor) {
            $this->line("  ✉️  {$executor->name} (TG ID: {$executor->telegram_id})");
        }

        // Подтверждение
        if (!$this->confirm("\n❓ Отправить тестовые уведомления?", true)) {
            $this->info('❌ Отменено');
            return 0;
        }

        // Вызываем событие
        $this->info("\n🚀 Отправка уведомлений...");
        event(new TicketCreated($ticket));

        $this->newLine();
        $this->info("✅ Событие TicketCreated вызвано!");
        $this->info("📋 Уведомления поставлены в очередь 'notifications'");
        $this->newLine();
        $this->comment("Проверьте логи для отслеживания отправки:");
        $this->line("  tail -f storage/logs/laravel.log");
        $this->newLine();
        $this->comment("Для обработки очереди запустите:");
        $this->line("  php artisan queue:work --queue=notifications");

        return 0;
    }
}
