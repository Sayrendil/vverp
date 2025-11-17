<?php

namespace App\Listeners;

use App\Events\TicketStatusChanged;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Отправка уведомления в Telegram при изменении статуса тикета
 */
class SendTicketStatusChangedTelegramNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';
    public $tries = 3;

    public function __construct(
        private TelegramBotService $bot
    ) {}

    public function handle(TicketStatusChanged $event): void
    {
        $ticket = $event->ticket;
        $author = $ticket->author;

        // Уведомляем автора заявки
        if (!$author || !$author->telegram_id) {
            Log::info('Author has no telegram_id, skipping status change notification', [
                'ticket_id' => $ticket->id,
                'author_id' => $ticket->author_id,
            ]);
            return;
        }

        $message = "🔄 <b>Изменен статус заявки #{$ticket->id}</b>\n\n" .
                   "📊 <b>Было:</b> {$event->oldStatus->name}\n" .
                   "📊 <b>Стало:</b> {$event->newStatus->name}\n\n" .
                   "⏰ " . now()->format('d.m.Y H:i');

        try {
            $this->bot->sendMessage($author->telegram_id, $message);

            Log::info('Status change notification sent to author', [
                'ticket_id' => $ticket->id,
                'author_id' => $author->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send status change notification', [
                'ticket_id' => $ticket->id,
                'author_id' => $author->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(TicketStatusChanged $event, \Throwable $exception): void
    {
        Log::error('SendTicketStatusChangedTelegramNotification failed', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
