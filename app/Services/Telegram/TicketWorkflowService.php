<?php

namespace App\Services\Telegram;

use App\Models\Ticket;
use App\Models\User;
use App\Enums\TicketStatus;
use App\Events\TicketStatusChanged;
use App\Events\TicketAssigned;
use App\Services\TelegramBotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для управления workflow заявок через Telegram
 *
 * Обрабатывает действия исполнителей и авторов с заявками
 */
class TicketWorkflowService
{
    public function __construct(
        private TelegramBotService $bot
    ) {}

    /**
     * Взять заявку в работу
     *
     * @param int $ticketId ID заявки
     * @param int $telegramId Telegram ID исполнителя
     * @param int $messageId ID сообщения для редактирования
     * @return array Результат операции
     */
    public function takeToWork(int $ticketId, int $telegramId, ?int $messageId = null): array
    {
        try {
            // Находим пользователя по telegram_id
            $executor = User::where('telegram_id', $telegramId)->first();

            if (!$executor) {
                return [
                    'success' => false,
                    'message' => '❌ Пользователь не найден. Пожалуйста, свяжите аккаунт с системой.',
                ];
            }

            // Находим заявку
            $ticket = Ticket::with(['ticketCategory', 'executor', 'status', 'author'])
                ->find($ticketId);

            if (!$ticket) {
                return [
                    'success' => false,
                    'message' => '❌ Заявка не найдена.',
                ];
            }

            // Проверяем, что пользователь является исполнителем этой категории
            if (!$executor->isExecutorInCategory($ticket->ticket_category_id)) {
                return [
                    'success' => false,
                    'message' => '❌ У вас нет прав на эту заявку.',
                ];
            }

            // Проверяем статус заявки
            if ($ticket->status_id !== TicketStatus::CREATED->value) {
                $currentExecutor = $ticket->executor ? $ticket->executor->name : 'неизвестен';
                return [
                    'success' => false,
                    'message' => "⚠️ Заявка уже в работе у: {$currentExecutor}",
                ];
            }

            // Назначаем исполнителя и меняем статус
            DB::transaction(function () use ($ticket, $executor) {
                $oldStatus = $ticket->status;

                $ticket->update([
                    'executor_id' => $executor->id,
                    'status_id' => TicketStatus::IN_PROGRESS->value,
                ]);

                $ticket->load(['status', 'executor', 'author', 'store', 'problem']);

                // Вызываем события
                event(new TicketAssigned($ticket, $executor));
                event(new TicketStatusChanged($ticket, $oldStatus, $ticket->status));
            });

            // Уведомляем автора с кнопкой подтверждения
            if ($ticket->author && $ticket->author->telegram_id) {
                $message = $this->formatAuthorNotification($ticket, $executor);
                $buttons = [
                    [
                        ['text' => '✅ Подтвердить выполнение', 'callback_data' => "confirm_complete:{$ticket->id}"],
                    ],
                    [
                        ['text' => '👁 Посмотреть детали', 'callback_data' => "view_ticket:{$ticket->id}"],
                    ],
                ];

                $this->bot->sendMessage(
                    $ticket->author->telegram_id,
                    $message,
                    ['reply_markup' => ['inline_keyboard' => $buttons]]
                );
            }

            // Редактируем сообщение исполнителя
            if ($messageId) {
                $this->updateMessageWithNewStatus($telegramId, $messageId, $ticket, 'В работе');
            }

            return [
                'success' => true,
                'message' => "✅ Заявка #{$ticket->id} взята в работу!",
                'ticket' => $ticket,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to take ticket to work', [
                'ticket_id' => $ticketId,
                'telegram_id' => $telegramId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '❌ Произошла ошибка. Попробуйте позже.',
            ];
        }
    }

    /**
     * Отложить заявку
     */
    public function postponeTicket(int $ticketId, int $telegramId, ?int $messageId = null): array
    {
        try {
            $executor = User::where('telegram_id', $telegramId)->first();

            if (!$executor) {
                return ['success' => false, 'message' => '❌ Пользователь не найден.'];
            }

            $ticket = Ticket::with(['status', 'executor', 'author'])->find($ticketId);

            if (!$ticket) {
                return ['success' => false, 'message' => '❌ Заявка не найдена.'];
            }

            // Проверяем, что это исполнитель заявки
            if ($ticket->executor_id !== $executor->id) {
                return ['success' => false, 'message' => '❌ Вы не являетесь исполнителем этой заявки.'];
            }

            // Проверяем текущий статус
            if ($ticket->status_id !== TicketStatus::IN_PROGRESS->value) {
                return ['success' => false, 'message' => '⚠️ Можно отложить только заявки в работе.'];
            }

            // Меняем статус
            DB::transaction(function () use ($ticket) {
                $oldStatus = $ticket->status;

                $ticket->update(['status_id' => TicketStatus::POSTPONED->value]);
                $ticket->load('status');

                event(new TicketStatusChanged($ticket, $oldStatus, $ticket->status));
            });

            // Уведомляем автора
            if ($ticket->author && $ticket->author->telegram_id) {
                $this->bot->sendMessage(
                    $ticket->author->telegram_id,
                    "⏸️ <b>Ваша заявка #{$ticket->id} отложена</b>\n\n" .
                    "👤 <b>Исполнитель:</b> {$executor->name}"
                );
            }

            if ($messageId) {
                $this->updateMessageWithNewStatus($telegramId, $messageId, $ticket, 'Отложена');
            }

            return [
                'success' => true,
                'message' => "⏸️ Заявка #{$ticket->id} отложена.",
            ];

        } catch (\Exception $e) {
            Log::error('Failed to postpone ticket', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => '❌ Произошла ошибка.'];
        }
    }

    /**
     * Напомнить автору о необходимости подтверждения (опционально)
     */
    public function remindAuthor(int $ticketId, int $telegramId): array
    {
        try {
            $executor = User::where('telegram_id', $telegramId)->first();

            if (!$executor) {
                return ['success' => false, 'message' => '❌ Пользователь не найден.'];
            }

            $ticket = Ticket::with(['status', 'executor', 'author'])->find($ticketId);

            if (!$ticket) {
                return ['success' => false, 'message' => '❌ Заявка не найдена.'];
            }

            // Проверяем права
            if ($ticket->executor_id !== $executor->id) {
                return ['success' => false, 'message' => '❌ Вы не являетесь исполнителем этой заявки.'];
            }

            // Проверяем статус
            if ($ticket->status_id !== TicketStatus::IN_PROGRESS->value) {
                return ['success' => false, 'message' => '⚠️ Заявка не в работе.'];
            }

            // Отправляем напоминание автору
            if ($ticket->author && $ticket->author->telegram_id) {
                $this->bot->sendMessage(
                    $ticket->author->telegram_id,
                    "⏰ <b>Напоминание о заявке #{$ticket->id}</b>\n\n" .
                    "👤 Исполнитель {$executor->name} напоминает:\n" .
                    "Пожалуйста, проверьте выполнение работы и подтвердите."
                );
            }

            return [
                'success' => true,
                'message' => "✅ Напоминание отправлено автору.",
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send reminder', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => '❌ Произошла ошибка.'];
        }
    }

    /**
     * Подтвердить выполнение заявки (автором)
     */
    public function confirmCompletion(int $ticketId, int $telegramId, ?int $messageId = null): array
    {
        try {
            $author = User::where('telegram_id', $telegramId)->first();

            if (!$author) {
                return ['success' => false, 'message' => '❌ Пользователь не найден.'];
            }

            $ticket = Ticket::with(['status', 'executor', 'author'])->find($ticketId);

            if (!$ticket) {
                return ['success' => false, 'message' => '❌ Заявка не найдена.'];
            }

            // Проверяем, что это автор заявки
            if ($ticket->author_id !== $author->id) {
                return ['success' => false, 'message' => '❌ Только автор может подтвердить выполнение.'];
            }

            // Проверяем статус заявки
            if ($ticket->status_id !== TicketStatus::IN_PROGRESS->value) {
                return ['success' => false, 'message' => '⚠️ Заявка не в работе.'];
            }

            // Завершаем заявку (сразу COMPLETED!)
            DB::transaction(function () use ($ticket) {
                $oldStatus = $ticket->status;

                // Сразу ставим статус ЗАВЕРШЕНА
                $ticket->update([
                    'status_id' => TicketStatus::COMPLETED->value
                ]);
                $ticket->load('status');

                event(new TicketStatusChanged($ticket, $oldStatus, $ticket->status));
            });

            // Уведомляем исполнителя
            if ($ticket->executor && $ticket->executor->telegram_id) {
                $this->bot->sendMessage(
                    $ticket->executor->telegram_id,
                    "🎉 <b>Заявка #{$ticket->id} завершена!</b>\n\n" .
                    "✅ Автор подтвердил выполнение работы"
                );
            }

            if ($messageId) {
                $this->updateMessageWithNewStatus($telegramId, $messageId, $ticket, 'Завершена');
            }

            return [
                'success' => true,
                'message' => "🎉 <b>Спасибо!</b>\n\nЗаявка #{$ticket->id} успешно завершена.",
            ];

        } catch (\Exception $e) {
            Log::error('Failed to confirm ticket completion', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => '❌ Произошла ошибка.'];
        }
    }

    /**
     * Вернуть заявку в работу (отклонить выполнение)
     */
    public function rejectCompletion(int $ticketId, int $telegramId, ?int $messageId = null): array
    {
        try {
            $author = User::where('telegram_id', $telegramId)->first();

            if (!$author) {
                return ['success' => false, 'message' => '❌ Пользователь не найден.'];
            }

            $ticket = Ticket::with(['status', 'executor', 'author'])->find($ticketId);

            if (!$ticket) {
                return ['success' => false, 'message' => '❌ Заявка не найдена.'];
            }

            if ($ticket->author_id !== $author->id) {
                return ['success' => false, 'message' => '❌ Только автор может вернуть заявку.'];
            }

            // Проверяем статус
            if ($ticket->status_id !== TicketStatus::IN_PROGRESS->value) {
                return ['success' => false, 'message' => '⚠️ Заявка не в работе.'];
            }

            // Логируем возврат
            Log::info('Ticket returned to work by author', [
                'ticket_id' => $ticket->id,
                'author_id' => $ticket->author_id,
                'executor_id' => $ticket->executor_id
            ]);

            // Уведомляем исполнителя
            if ($ticket->executor && $ticket->executor->telegram_id) {
                $this->bot->sendMessage(
                    $ticket->executor->telegram_id,
                    "🔄 <b>Заявка #{$ticket->id} возвращена в работу</b>\n\n" .
                    "❌ Автор не подтвердил выполнение"
                );
            }

            if ($messageId) {
                $this->updateMessageWithNewStatus($telegramId, $messageId, $ticket, 'Возвращена в работу');
            }

            return [
                'success' => true,
                'message' => "🔄 Заявка #{$ticket->id} возвращена в работу.",
            ];

        } catch (\Exception $e) {
            Log::error('Failed to reject ticket completion', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => '❌ Произошла ошибка.'];
        }
    }

    /**
     * Форматирование уведомления автору о взятии заявки в работу
     */
    private function formatAuthorNotification(Ticket $ticket, User $executor): string
    {
        $store = $ticket->store ? $ticket->store->name : 'Не указан';
        $problem = $ticket->problem ? $ticket->problem->name : 'Не указана';

        return "✅ <b>Ваша заявка #{$ticket->id} взята в работу</b>\n\n" .
               "🏪 <b>Магазин:</b> {$store}\n" .
               "❗ <b>Проблема:</b> {$problem}\n" .
               "👤 <b>Исполнитель:</b> {$executor->name}\n\n" .
               "📝 <b>Что делать?</b>\n" .
               "После завершения работы нажмите кнопку\n" .
               "\"<b>Подтвердить выполнение</b>\" для закрытия заявки.";
    }

    /**
     * Обновить сообщение с новым статусом
     */
    private function updateMessageWithNewStatus(int $chatId, int $messageId, Ticket $ticket, string $statusText): void
    {
        try {
            $text = "📌 <b>Заявка #{$ticket->id}</b>\n\n" .
                    "📊 <b>Статус:</b> {$statusText}\n" .
                    "⏰ " . now()->format('d.m.Y H:i');

            $this->bot->editMessage($chatId, $messageId, $text);
        } catch (\Exception $e) {
            Log::warning('Failed to update message', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
