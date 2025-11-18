<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Отправка уведомлений всем исполнителям категории о новой заявке
 *
 * Уведомляются только активные исполнители с telegram_id
 */
class SendTicketCreatedNotificationToExecutors implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Название очереди для обработки
     */
    public string $queue = 'notifications';

    /**
     * Количество попыток
     */
    public int $tries = 3;

    /**
     * Таймаут выполнения (секунды)
     */
    public int $timeout = 120;

    public function __construct(
        private TelegramBotService $bot
    ) {}

    /**
     * Обработка события создания заявки
     */
    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;

        // Получаем категорию заявки
        $categoryId = $ticket->ticket_category_id;

        if (!$categoryId) {
            Log::info('Ticket has no category, skipping executor notifications', [
                'ticket_id' => $ticket->id,
            ]);
            return;
        }

        // Получаем всех активных исполнителей категории с telegram_id
        $executors = $ticket->ticketCategory
            ->activeExecutors()
            ->whereNotNull('telegram_id')
            ->get();

        if ($executors->isEmpty()) {
            Log::info('No executors with telegram_id found for category', [
                'ticket_id' => $ticket->id,
                'category_id' => $categoryId,
            ]);
            return;
        }

        Log::info('Sending notifications to executors', [
            'ticket_id' => $ticket->id,
            'category_id' => $categoryId,
            'executors_count' => $executors->count(),
        ]);

        // Формируем сообщение и кнопки
        $message = $this->formatMessage($ticket);
        $buttons = $this->getActionButtons($ticket);

        // Отправляем уведомление каждому исполнителю
        foreach ($executors as $executor) {
            try {
                // Отправляем основное сообщение с кнопками
                $this->bot->sendMessage(
                    $executor->telegram_id,
                    $message,
                    ['reply_markup' => ['inline_keyboard' => $buttons]]
                );

                // Отправляем вложения (если есть)
                $this->sendAttachments($ticket, $executor->telegram_id);

                Log::info('Notification sent to executor', [
                    'ticket_id' => $ticket->id,
                    'executor_id' => $executor->id,
                    'executor_name' => $executor->name,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send notification to executor', [
                    'ticket_id' => $ticket->id,
                    'executor_id' => $executor->id,
                    'executor_name' => $executor->name,
                    'error' => $e->getMessage(),
                ]);

                // Не прерываем цикл, продолжаем отправлять другим
                continue;
            }
        }
    }

    /**
     * Форматирование сообщения о новой заявке
     */
    private function formatMessage($ticket): string
    {
        $store = $ticket->store ? $ticket->store->name : 'Не указан';
        $category = $ticket->ticketCategory ? $ticket->ticketCategory->name : 'Не указана';
        $problem = $ticket->problem ? $ticket->problem->name : 'Не указана';
        $author = $ticket->author ? $ticket->author->name : 'Неизвестен';
        $status = $ticket->status ? $ticket->status->name : 'Не указан';

        $message = "🆕 <b>Новая заявка #{$ticket->id}</b>\n\n";
        $message .= "📁 <b>Категория:</b> {$category}\n";
        $message .= "🏪 <b>Магазин:</b> {$store}\n";
        $message .= "❗ <b>Проблема:</b> {$problem}\n";
        $message .= "👤 <b>Автор:</b> {$author}\n";
        $message .= "📊 <b>Статус:</b> {$status}\n\n";

        if ($ticket->title) {
            $message .= "📝 <b>Заголовок:</b>\n{$ticket->title}\n\n";
        }

        if ($ticket->description) {
            $message .= "📄 <b>Описание:</b>\n{$ticket->description}\n\n";
        }

        $message .= "⏰ <b>Создана:</b> " . $ticket->created_at->format('d.m.Y H:i');

        return $message;
    }

    /**
     * Получить кнопки для действий с заявкой
     */
    private function getActionButtons($ticket): array
    {
        return [
            [
                ['text' => '✅ Взять в работу', 'callback_data' => "take_work:{$ticket->id}"],
            ],
            [
                ['text' => '👁 Подробнее', 'callback_data' => "view_ticket:{$ticket->id}"],
            ],
        ];
    }

    /**
     * Отправить вложения заявки в Telegram
     */
    private function sendAttachments($ticket, string $telegramId): void
    {
        $attachments = $ticket->attachments;

        if ($attachments->isEmpty()) {
            return;
        }

        foreach ($attachments as $attachment) {
            try {
                $caption = "📎 Вложение к заявке #{$ticket->id}";

                // Используем telegram_file_id если есть (для файлов загруженных через Telegram)
                if ($attachment->telegram_file_id) {
                    match($attachment->file_type) {
                        'photo' => $this->bot->sendPhoto($telegramId, $attachment->telegram_file_id, $caption),
                        'video' => $this->bot->sendVideo($telegramId, $attachment->telegram_file_id, $caption),
                        'document' => $this->bot->sendDocument($telegramId, $attachment->telegram_file_id, $caption),
                        default => null,
                    };
                } else if ($attachment->file_path) {
                    // Для файлов загруженных через веб - используем публичный URL
                    $filePath = storage_path('app/public/' . $attachment->file_path);

                    if (file_exists($filePath)) {
                        $publicUrl = url('storage/' . $attachment->file_path);

                        match($attachment->file_type) {
                            'photo' => $this->bot->sendPhoto($telegramId, $publicUrl, $caption),
                            'video' => $this->bot->sendVideo($telegramId, $publicUrl, $caption),
                            'document' => $this->bot->sendDocument($telegramId, $publicUrl, $caption),
                            default => null,
                        };
                    } else {
                        Log::warning('Attachment file not found', [
                            'ticket_id' => $ticket->id,
                            'attachment_id' => $attachment->id,
                            'file_path' => $filePath,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to send attachment', [
                    'ticket_id' => $ticket->id,
                    'attachment_id' => $attachment->id,
                    'error' => $e->getMessage(),
                ]);
                // Продолжаем отправлять остальные вложения
                continue;
            }
        }
    }

    /**
     * Обработка провала задачи после всех попыток
     */
    public function failed(TicketCreated $event, \Throwable $exception): void
    {
        Log::error('SendTicketCreatedNotificationToExecutors failed', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
