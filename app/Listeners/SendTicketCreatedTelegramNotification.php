<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Models\Ticket;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Отправка уведомления в Telegram при создании тикета
 *
 * Implements ShouldQueue - будет выполняться асинхронно в очереди
 */
class SendTicketCreatedTelegramNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';
    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        private TelegramBotService $bot
    ) {}

    /**
     * Обработка события
     */
    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;

        // Получаем пользователя создавшего тикет
        $user = $ticket->author;

        // Проверяем есть ли у пользователя Telegram ID
        if (!$user->telegram_id) {
            Log::info('User has no telegram_id', ['user_id' => $user->id]);
            return;
        }

        // Формируем сообщение
        $message = $this->formatMessage($ticket);

        // Отправляем уведомление
        try {
            $this->bot->sendMessage($user->telegram_id, $message);

            Log::info('Telegram notification sent', [
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send telegram notification', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Для повторной попытки в очереди
        }
    }

    /**
     * Форматирование сообщения о тикете
     */
    private function formatMessage(Ticket $ticket): string
    {
        $store = $ticket->store ? $ticket->store->name : 'Не указан';
        $category = $ticket->ticketCategory ? $ticket->ticketCategory->name : 'Не указана';
        $problem = $ticket->problem ? $ticket->problem->name : 'Не указана';
        $status = $ticket->status ? $ticket->status->name : 'Не указан';

        return "🆕 <b>Новая заявка #{$ticket->id}</b>\n\n" .
               "🏪 <b>Магазин:</b> {$store}\n" .
               "📁 <b>Категория:</b> {$category}\n" .
               "🔧 <b>Проблема:</b> {$problem}\n" .
               "📊 <b>Статус:</b> {$status}\n\n" .
               "📝 <b>Описание:</b>\n" .
               mb_substr($ticket->description, 0, 200) .
               (mb_strlen($ticket->description) > 200 ? '...' : '') . "\n\n" .
               "⏰ <b>Создана:</b> " . $ticket->created_at->format('d.m.Y H:i');
    }

    /**
     * Обработка ошибки при выполнении задачи
     */
    public function failed(TicketCreated $event, \Throwable $exception): void
    {
        Log::error('SendTicketCreatedTelegramNotification failed', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
