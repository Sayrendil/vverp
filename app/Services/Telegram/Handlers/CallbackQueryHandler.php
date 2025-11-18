<?php

namespace App\Services\Telegram\Handlers;

use App\Services\TelegramBotService;
use App\Services\TelegramWizardService;
use App\Services\Telegram\TicketWorkflowService;
use App\Models\TelegramSession;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик callback queries (нажатия на inline кнопки)
 *
 * Отвечает за обработку всех нажатий на кнопки под сообщениями
 */
class CallbackQueryHandler implements UpdateHandler
{
    public function __construct(
        private TelegramBotService $bot,
        private TelegramWizardService $wizard,
        private TicketWorkflowService $workflow
    ) {}

    public function supports(array $update): bool
    {
        return isset($update['callback_query']);
    }

    public function handle(array $update): void
    {
        $callbackQuery = $update['callback_query'];

        $callbackId = $callbackQuery['id'] ?? null;
        $data = $callbackQuery['data'] ?? null;
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $userId = $callbackQuery['from']['id'] ?? null;
        $username = $callbackQuery['from']['username'] ?? 'unknown';

        if (!$callbackId || !$data || !$chatId || !$userId) {
            return;
        }

        Log::info("Telegram Callback: {$data} from @{$username} (ID: {$userId})");

        // Отвечаем на callback query (убираем "часики" на кнопке)
        $this->bot->answerCallbackQuery($callbackId);

        // Проверяем, это действие с заявкой или действие wizard'а
        if ($this->isTicketAction($data)) {
            // Действия с заявками не требуют сессии
            $messageId = $callbackQuery['message']['message_id'] ?? null;
            $this->handleTicketAction($data, $userId, $chatId, $messageId);
            return;
        }

        // Для wizard'а нужна сессия
        $session = TelegramSession::where('telegram_user_id', $userId)->first();

        if (!$session) {
            $this->bot->sendMessage($chatId, "❌ Сессия не найдена. Используйте /start");
            return;
        }

        // Разбираем callback data (формат: type_id или просто action)
        $this->parseAndHandle($data, $session, $chatId);
    }

    private function parseAndHandle(string $data, TelegramSession $session, int $chatId): void
    {
        // Разбираем данные
        $parts = explode('_', $data, 2);
        $type = $parts[0];
        $id = isset($parts[1]) ? (int)$parts[1] : null;

        // Диспетчеризация по типу callback
        match($type) {
            'store' => $this->handleStore($session, $id, $chatId),
            'category' => $this->handleCategory($session, $id, $chatId),
            'problem' => $this->handleProblem($session, $id, $chatId),
            'skip' => $this->handleSkip($session, $data, $chatId),
            'confirm' => $this->handleConfirm($session, $data, $chatId),
            'attach' => $this->handleAttach($session, $chatId),
            default => $this->bot->sendMessage($chatId, "❓ Неизвестное действие"),
        };
    }

    private function handleStore(TelegramSession $session, ?int $id, int $chatId): void
    {
        if ($id === null) {
            $this->bot->sendMessage($chatId, "❌ Некорректные данные");
            return;
        }

        $this->wizard->handleStoreSelection($session, $id, $chatId);
    }

    private function handleCategory(TelegramSession $session, ?int $id, int $chatId): void
    {
        if ($id === null) {
            $this->bot->sendMessage($chatId, "❌ Некорректные данные");
            return;
        }

        $this->wizard->handleCategorySelection($session, $id, $chatId);
    }

    private function handleProblem(TelegramSession $session, ?int $id, int $chatId): void
    {
        if ($id === null) {
            $this->bot->sendMessage($chatId, "❌ Некорректные данные");
            return;
        }

        $this->wizard->handleProblemSelection($session, $id, $chatId);
    }

    private function handleSkip(TelegramSession $session, string $data, int $chatId): void
    {
        if ($data === 'skip_attach') {
            $this->wizard->skipAttachment($session, $chatId);
        }
    }

    private function handleConfirm(TelegramSession $session, string $data, int $chatId): void
    {
        if ($data === 'confirm_create') {
            $this->wizard->createTicket($session, $chatId);
        } elseif ($data === 'confirm_cancel') {
            $this->wizard->cancel($session, $chatId);
        }
    }

    private function handleAttach(TelegramSession $session, int $chatId): void
    {
        $this->bot->sendMessage($chatId,
            "📎 Отправьте фото, видео или файл.\n\n" .
            "Или используйте /skip чтобы пропустить этот шаг."
        );
    }

    /**
     * Проверить, является ли callback действием с заявкой
     */
    private function isTicketAction(string $data): bool
    {
        $ticketActions = [
            'take_work',
            'postpone',
            'remind_author',
            'confirm_complete',
            'reject_complete',
            'view_ticket',
        ];

        foreach ($ticketActions as $action) {
            if (str_starts_with($data, $action . ':')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Обработать действие с заявкой
     */
    private function handleTicketAction(string $data, int $telegramId, int $chatId, ?int $messageId): void
    {
        // Парсим action:ticket_id
        $parts = explode(':', $data, 2);
        if (count($parts) !== 2) {
            $this->bot->sendMessage($chatId, "❌ Некорректный формат данных");
            return;
        }

        $action = $parts[0];
        $ticketId = (int)$parts[1];

        // Диспетчеризация по действию
        $result = match($action) {
            'take_work' => $this->workflow->takeToWork($ticketId, $telegramId, $messageId),
            'postpone' => $this->workflow->postponeTicket($ticketId, $telegramId, $messageId),
            'remind_author' => $this->workflow->remindAuthor($ticketId, $telegramId),
            'confirm_complete' => $this->workflow->confirmCompletion($ticketId, $telegramId, $messageId),
            'reject_complete' => $this->workflow->rejectCompletion($ticketId, $telegramId, $messageId),
            'view_ticket' => $this->handleViewTicket($ticketId, $chatId),
            default => ['success' => false, 'message' => '❓ Неизвестное действие'],
        };

        // Отправляем ответ пользователю
        if (isset($result['message'])) {
            $this->bot->sendMessage($chatId, $result['message']);
        }

        // Если заявка взята в работу, показываем кнопки управления
        if ($action === 'take_work' && $result['success']) {
            $this->sendWorkflowButtons($chatId, $ticketId);
        }
    }

    /**
     * Показать детали заявки
     */
    private function handleViewTicket(int $ticketId, int $chatId): array
    {
        $ticket = \App\Models\Ticket::with(['store', 'problem', 'status', 'executor', 'ticketCategory', 'attachments'])
            ->find($ticketId);

        if (!$ticket) {
            return ['success' => false, 'message' => '❌ Заявка не найдена'];
        }

        $store = $ticket->store ? $ticket->store->name : 'Не указан';
        $category = $ticket->ticketCategory ? $ticket->ticketCategory->name : 'Не указана';
        $problem = $ticket->problem ? $ticket->problem->name : 'Не указана';
        $status = $ticket->status ? $ticket->status->name : 'Не указан';
        $executor = $ticket->executor ? $ticket->executor->name : 'Не назначен';

        $message = "📋 <b>Детали заявки #{$ticket->id}</b>\n\n";
        $message .= "📁 <b>Категория:</b> {$category}\n";
        $message .= "🏪 <b>Магазин:</b> {$store}\n";
        $message .= "❗ <b>Проблема:</b> {$problem}\n";
        $message .= "📊 <b>Статус:</b> {$status}\n";
        $message .= "👤 <b>Исполнитель:</b> {$executor}\n\n";

        if ($ticket->title) {
            $message .= "📝 <b>Заголовок:</b>\n{$ticket->title}\n\n";
        }

        if ($ticket->description) {
            $message .= "📄 <b>Описание:</b>\n{$ticket->description}\n\n";
        }

        $message .= "⏰ <b>Создана:</b> " . $ticket->created_at->format('d.m.Y H:i');

        // Отправляем основное сообщение
        $this->bot->sendMessage($chatId, $message);

        // Отправляем вложения, если они есть
        if ($ticket->attachments && $ticket->attachments->count() > 0) {
            foreach ($ticket->attachments as $index => $attachment) {
                $caption = $index === 0 ? "📎 Вложение к заявке #{$ticket->id}" : null;

                try {
                    // Используем telegram_file_id если есть, это быстрее
                    if ($attachment->telegram_file_id) {
                        match($attachment->file_type) {
                            'photo' => $this->bot->sendPhoto($chatId, $attachment->telegram_file_id, $caption),
                            'video' => $this->bot->sendVideo($chatId, $attachment->telegram_file_id, $caption),
                            'document' => $this->bot->sendDocument($chatId, $attachment->telegram_file_id, $caption),
                            default => null,
                        };
                    } else if ($attachment->file_path) {
                        // Fallback: отправляем файл по публичному URL (для заявок созданных через веб)
                        $filePath = storage_path('app/public/' . $attachment->file_path);

                        if (file_exists($filePath)) {
                            // Генерируем публичный URL для файла
                            $publicUrl = url('storage/' . $attachment->file_path);

                            match($attachment->file_type) {
                                'photo' => $this->bot->sendPhoto($chatId, $publicUrl, $caption),
                                'video' => $this->bot->sendVideo($chatId, $publicUrl, $caption),
                                'document' => $this->bot->sendDocument($chatId, $publicUrl, $caption),
                                default => null,
                            };
                        } else {
                            Log::warning('Attachment file not found', [
                                'ticket_id' => $ticketId,
                                'attachment_id' => $attachment->id,
                                'file_path' => $filePath,
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send attachment', [
                        'ticket_id' => $ticketId,
                        'attachment_id' => $attachment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return ['success' => true, 'message' => null]; // message уже отправлено
    }

    /**
     * Отправить кнопки управления заявкой в работе
     */
    private function sendWorkflowButtons(int $chatId, int $ticketId): void
    {
        $buttons = [
            [
                ['text' => '⏸️ Отложить', 'callback_data' => "postpone:{$ticketId}"],
            ],
            [
                ['text' => '💬 Напомнить автору', 'callback_data' => "remind_author:{$ticketId}"],
            ],
            [
                ['text' => '👁 Подробнее', 'callback_data' => "view_ticket:{$ticketId}"],
            ],
        ];

        $this->bot->sendMessage(
            $chatId,
            "📌 <b>Управление заявкой</b>\n\n" .
            "Заявка в работе.\n\n" .
            "💡 <i>Автор может подтвердить выполнение в любой момент</i>",
            ['reply_markup' => ['inline_keyboard' => $buttons]]
        );
    }
}
