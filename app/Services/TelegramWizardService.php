<?php

namespace App\Services;

use App\Models\User;
use App\Models\Store;
use App\Models\Problem;
use App\Models\TicketCategory;
use App\Models\TelegramSession;
use App\Enums\TelegramStep;
use App\Enums\TicketStatus;
use App\Services\Telegram\ValidationService;
use Illuminate\Support\Facades\Log;

class TelegramWizardService
{
    public function __construct(
        private TelegramBotService $bot,
        private TicketService $ticketService,
        private ValidationService $validator
    ) {}

    /**
     * Начать процесс создания заявки
     */
    public function start(string $telegramUserId, int $chatId): void
    {
        // Проверяем зарегистрирован ли пользователь
        $user = User::where('telegram_id', $telegramUserId)->first();

        if (!$user) {
            $this->bot->sendMessage($chatId,
                "❌ Вы не зарегистрированы в системе.\n\n" .
                "Обратитесь к администратору для регистрации."
            );
            return;
        }

        // Создаем новую сессию (сбрасываем старую)
        $session = TelegramSession::updateOrCreate(
            ['telegram_user_id' => $telegramUserId],
            [
                'user_id' => $user->id,
                'step' => TelegramStep::IDLE,
                'data' => null, // Очищаем данные
                'message_id' => null, // Сбрасываем message_id
                'expires_at' => now()->addMinutes(30),
            ]
        );

        // Определяем с какого шага начать
        $this->processStep($session, $chatId, $user);
    }

    /**
     * Построить полный текст сообщения wizard'а с прогресс-баром
     */
    private function buildWizardMessage(TelegramSession $session, User $user, string $currentStepText, array $buttons = []): string
    {
        $data = $session->data ?? [];

        // Определяем прогресс
        $progress = $this->getProgress($session);
        $progressBar = $this->buildProgressBar($progress);

        $message = "🎫 <b>Создание заявки</b>\n\n";
        $message .= "{$progressBar}\n\n";

        // Собранные данные
        $collectedData = [];

        // Магазин
        $store = $user->store ?? ($data['store_id'] ?? null ? Store::find($data['store_id']) : null);
        if ($store) {
            $collectedData[] = "✅ <b>Магазин:</b> {$store->name}";
        }

        // Категория
        $category = $user->ticketCategory ?? ($data['ticket_category_id'] ?? null ? TicketCategory::find($data['ticket_category_id']) : null);
        if ($category) {
            $collectedData[] = "✅ <b>Категория:</b> {$category->name}";
        }

        // Проблема
        if ($data['problem_id'] ?? null) {
            $problem = Problem::find($data['problem_id']);
            if ($problem) {
                $collectedData[] = "✅ <b>Проблема:</b> {$problem->name}";
            }
        }

        // Описание
        if ($data['description'] ?? null) {
            $description = mb_strlen($data['description']) > 50
                ? mb_substr($data['description'], 0, 50) . '...'
                : $data['description'];
            $collectedData[] = "✅ <b>Описание:</b> {$description}";
        }

        // Файлы
        if (!empty($data['attachments'] ?? [])) {
            $fileCount = count($data['attachments']);
            $collectedData[] = "✅ <b>Файлы:</b> прикреплено {$fileCount} шт.";
        }

        if (!empty($collectedData)) {
            $message .= implode("\n", $collectedData) . "\n\n";
        }

        $message .= "━━━━━━━━━━━━━━━━━\n\n";
        $message .= $currentStepText;

        return $message;
    }

    /**
     * Построить прогресс-бар
     */
    private function buildProgressBar(float $progress): string
    {
        $total = 10;
        $filled = (int)round($progress * $total);
        $empty = $total - $filled;

        $bar = str_repeat('█', $filled) . str_repeat('░', $empty);
        $percentage = (int)($progress * 100);

        return "📊 Прогресс: {$bar} {$percentage}%";
    }

    /**
     * Получить прогресс выполнения
     */
    private function getProgress(TelegramSession $session): float
    {
        return match($session->step) {
            TelegramStep::IDLE => 0.0,
            TelegramStep::SELECT_STORE => 0.1,
            TelegramStep::SELECT_CATEGORY => 0.3,
            TelegramStep::SELECT_PROBLEM => 0.5,
            TelegramStep::ENTER_DESCRIPTION => 0.7,
            TelegramStep::UPLOAD_FILE => 0.85,
            TelegramStep::CONFIRM => 0.95,
            default => 0.0,
        };
    }

    /**
     * Отправить или обновить сообщение wizard'а
     *
     * @param bool $forceNew Принудительно создать новое сообщение (удалив старое)
     */
    private function sendOrUpdateWizard(TelegramSession $session, int $chatId, string $messageText, array $buttons, bool $forceNew = false): void
    {
        if ($session->hasWizardMessage() && !$forceNew) {
            // Редактируем существующее сообщение
            $result = $this->bot->editMessageWithButtons(
                $chatId,
                $session->getMessageId(),
                $messageText,
                $buttons
            );
        } else {
            // Если нужно создать новое - удаляем старое
            if ($session->hasWizardMessage()) {
                $this->bot->deleteMessage($chatId, $session->getMessageId());
            }

            // Создаём новое сообщение
            $result = $this->bot->sendMessageWithButtons($chatId, $messageText, $buttons);

            if ($result && isset($result['message_id'])) {
                $session->setMessageId($result['message_id']);
            }
        }
    }

    /**
     * Обработать текущий шаг
     */
    private function processStep(TelegramSession $session, int $chatId, User $user): void
    {
        // Шаг 1: Выбор категории (если у пользователя нет категории или она = 0)
        $userCategoryId = $user->ticket_category_id;
        $sessionCategoryId = $session->getData('ticket_category_id');

        if ((!$userCategoryId || $userCategoryId == 0) && !$sessionCategoryId) {
            $this->askCategory($session, $chatId);
            return;
        }

        // Определяем выбранную категорию
        $selectedCategoryId = $sessionCategoryId ?? $userCategoryId;

        // Шаг 2: Если выбрана категория "Магазин" (ID=1) и нет магазина - просим выбрать
        if ($selectedCategoryId == 1) {
            if (!$user->store_id && !$session->getData('store_id')) {
                $this->askStore($session, $chatId);
                return;
            }
        }

        // Шаг 3: Выбор проблемы
        if (!$session->getData('problem_id')) {
            $this->askProblem($session, $chatId);
            return;
        }

        // Все данные собраны
        $this->bot->sendMessage($chatId, "✅ Все шаги пройдены!");
    }

    /**
     * Запросить выбор магазина
     */
    private function askStore(TelegramSession $session, int $chatId): void
    {
        $session->update(['step' => TelegramStep::SELECT_STORE]);

        $stores = Store::all();
        $buttons = [];

        foreach ($stores as $store) {
            $buttons[] = [
                ['text' => "🏪 {$store->name}", 'callback_data' => "store_{$store->id}"]
            ];
        }

        $user = $session->user;
        $messageText = $this->buildWizardMessage(
            $session,
            $user,
            "🏪 <b>Шаг 2 из 5:</b> Выберите магазин"
        );

        $this->sendOrUpdateWizard($session, $chatId, $messageText, $buttons);
    }

    /**
     * Запросить выбор категории
     */
    private function askCategory(TelegramSession $session, int $chatId): void
    {
        $session->update(['step' => TelegramStep::SELECT_CATEGORY]);

        $categories = TicketCategory::all();
        $buttons = [];

        foreach ($categories as $category) {
            $buttons[] = [
                ['text' => "📁 {$category->name}", 'callback_data' => "category_{$category->id}"]
            ];
        }

        $user = $session->user;
        $messageText = $this->buildWizardMessage(
            $session,
            $user,
            "📁 <b>Шаг 1 из 5:</b> Выберите категорию"
        );

        $this->sendOrUpdateWizard($session, $chatId, $messageText, $buttons);
    }

    /**
     * Запросить выбор проблемы
     */
    private function askProblem(TelegramSession $session, int $chatId): void
    {
        $session->update(['step' => TelegramStep::SELECT_PROBLEM]);

        $problems = Problem::all();
        $buttons = [];

        foreach ($problems->chunk(2) as $chunk) {
            $row = [];
            foreach ($chunk as $problem) {
                $row[] = ['text' => $problem->name, 'callback_data' => "problem_{$problem->id}"];
            }
            $buttons[] = $row;
        }

        $user = $session->user;
        $messageText = $this->buildWizardMessage(
            $session,
            $user,
            "🔧 <b>Шаг 3 из 5:</b> Выберите проблему"
        );

        $this->sendOrUpdateWizard($session, $chatId, $messageText, $buttons);
    }

    /**
     * Обработать выбор магазина
     */
    public function handleStoreSelection(TelegramSession $session, int $storeId, int $chatId): void
    {
        // Валидация ID
        $validation = $this->validator->validateId($storeId, 'магазина');
        if (!$validation['valid']) {
            // Для ошибок валидации показываем alert
            return;
        }

        $store = Store::find($storeId);

        if (!$store) {
            return;
        }

        $session->setData('store_id', $storeId);

        $user = $session->user;
        $this->processStep($session, $chatId, $user);
    }

    /**
     * Обработать выбор категории
     */
    public function handleCategorySelection(TelegramSession $session, int $categoryId, int $chatId): void
    {
        // Валидация ID
        $validation = $this->validator->validateId($categoryId, 'категории');
        if (!$validation['valid']) {
            return;
        }

        $category = TicketCategory::find($categoryId);

        if (!$category) {
            return;
        }

        $session->setData('ticket_category_id', $categoryId);

        $user = $session->user;
        $this->processStep($session, $chatId, $user);
    }

    /**
     * Обработать выбор проблемы
     */
    public function handleProblemSelection(TelegramSession $session, int $problemId, int $chatId): void
    {
        // Валидация ID
        $validation = $this->validator->validateId($problemId, 'проблемы');
        if (!$validation['valid']) {
            return;
        }

        $problem = Problem::find($problemId);

        if (!$problem) {
            return;
        }

        $session->setData('problem_id', $problemId);
        $session->update(['step' => TelegramStep::ENTER_DESCRIPTION]);

        $user = $session->user;
        $messageText = $this->buildWizardMessage(
            $session,
            $user,
            "📝 <b>Шаг 4 из 5:</b> Опишите проблему подробно\n\n" .
            "💡 <i>Минимум 20 символов. Просто отправьте текстовое сообщение.</i>"
        );

        // Для ввода текста не нужны кнопки - редактируем сообщение без них
        if ($session->hasWizardMessage()) {
            $this->bot->editMessage($chatId, $session->getMessageId(), $messageText);
        }
    }

    /**
     * Обработать описание
     */
    public function handleDescription(TelegramSession $session, string $description, int $chatId, ?int $userMessageId = null): void
    {
        // Валидация описания
        $validation = $this->validator->validateDescription($description);

        if (!$validation['valid']) {
            $this->bot->sendMessage($chatId,
                "❌ " . $validation['error'] . "\n\n" .
                "💡 Попробуйте ещё раз:"
            );
            return;
        }

        // Удаляем сообщение пользователя для чистоты чата
        if ($userMessageId) {
            $this->bot->deleteMessage($chatId, $userMessageId);
        }

        // Санитизация для безопасности
        $description = $this->validator->sanitizeHtml($description);

        $session->setData('description', $description);
        $session->update(['step' => TelegramStep::UPLOAD_FILE]);

        $user = $session->user;
        $buttons = [
            [['text' => '📎 Прикрепить файл', 'callback_data' => 'attach']],
            [['text' => '⏭️ Пропустить', 'callback_data' => 'skip_attach']],
        ];

        $messageText = $this->buildWizardMessage(
            $session,
            $user,
            "📎 <b>Шаг 5 из 5:</b> Хотите прикрепить фото или файл?"
        );

        // Отправляем новое сообщение вместо редактирования, чтобы оно было внизу
        $this->sendOrUpdateWizard($session, $chatId, $messageText, $buttons, forceNew: true);
    }

    /**
     * Обновить прогресс прикрепления файлов
     */
    public function updateAttachmentProgress(TelegramSession $session, int $chatId, int $attachmentCount): void
    {
        $user = $session->user;
        $buttons = [
            [['text' => '📎 Прикрепить ещё', 'callback_data' => 'attach']],
            [['text' => '✅ Готово, продолжить', 'callback_data' => 'skip_attach']],
        ];

        $messageText = $this->buildWizardMessage(
            $session,
            $user,
            "📎 <b>Шаг 5 из 5:</b> Прикрепление файлов\n\n" .
            "✅ Прикреплено файлов: <b>{$attachmentCount}</b>\n\n" .
            "Можете прикрепить ещё файлы или продолжить."
        );

        // Создаём новое сообщение внизу после файла пользователя
        $this->sendOrUpdateWizard($session, $chatId, $messageText, $buttons, forceNew: true);
    }

    /**
     * Пропустить прикрепление файлов
     */
    public function skipAttachment(TelegramSession $session, int $chatId): void
    {
        $session->update(['step' => TelegramStep::CONFIRM]);
        $this->showPreview($session, $chatId);
    }

    /**
     * Показать превью заявки
     */
    private function showPreview(TelegramSession $session, int $chatId): void
    {
        $user = $session->user;
        $data = $session->data;

        // Получаем данные для превью
        $store = $user->store ?? Store::find($data['store_id'] ?? null);
        $category = $user->ticketCategory ?? TicketCategory::find($data['ticket_category_id'] ?? null);
        $problem = Problem::find($data['problem_id'] ?? null);
        $description = $data['description'] ?? '';

        $buttons = [
            [['text' => '✅ Создать заявку', 'callback_data' => 'confirm_create']],
            [['text' => '❌ Отменить', 'callback_data' => 'confirm_cancel']],
        ];

        $stepText = "━━━━━━━━━━━━━━━━━\n\n";
        $stepText .= "✅ <b>Все данные собраны!</b>\n\n";
        $stepText .= "📋 <b>Проверьте информацию и подтвердите создание заявки:</b>";

        $messageText = $this->buildWizardMessage($session, $user, $stepText);

        $this->sendOrUpdateWizard($session, $chatId, $messageText, $buttons);
    }

    /**
     * Создать заявку
     */
    public function createTicket(TelegramSession $session, int $chatId): void
    {
        $user = $session->user;
        $data = $session->data;

        // Логирование данных сессии для отладки
        Log::info('Creating ticket from Telegram', [
            'user_id' => $user->id,
            'user_category_id' => $user->ticket_category_id,
            'session_data' => $data,
        ]);

        // Валидация данных сессии перед созданием
        $validation = $this->validator->validateSessionData($data);
        if (!$validation['valid']) {
            $errors = implode("\n", $validation['errors']);
            $this->bot->sendMessage($chatId,
                "❌ Ошибки валидации:\n\n{$errors}\n\n" .
                "Используйте /cancel для отмены и /start для начала заново."
            );
            return;
        }

        try {
            // Формируем данные для создания тикета
            $ticketData = [
                'title' => 'Заявка из Telegram',
                'description' => $data['description'] ?? '',
                'problem_id' => $data['problem_id'] ?? null,
                'status_id' => TicketStatus::CREATED->value, // "Создана"
                'ticket_category_id' => $data['ticket_category_id'] ?? ($user->ticket_category_id > 0 ? $user->ticket_category_id : null),
                'store_id' => $data['store_id'] ?? ($user->store_id > 0 ? $user->store_id : null),
                'created_via' => 'telegram', // Указываем источник создания
            ];

            Log::info('Ticket data prepared', [
                'ticket_data' => $ticketData,
            ]);

            // Создаем тикет через сервис
            $ticket = $this->ticketService->createTicket($user, $ticketData);

            // Сохраняем вложения
            $attachments = $data['attachments'] ?? [];
            if (!empty($attachments)) {
                foreach ($attachments as $index => $attachment) {
                    // Генерируем уникальное имя файла
                    $extension = match($attachment['type']) {
                        'photo' => 'jpg',
                        'video' => 'mp4',
                        'document' => 'file',
                        default => 'bin',
                    };

                    $fileName = "ticket_{$ticket->id}_{$index}_" . time() . ".{$extension}";
                    $savePath = "attachments/{$fileName}";

                    // Скачиваем файл из Telegram
                    $downloadedPath = $this->bot->downloadFile($attachment['file_id'], $savePath);

                    if ($downloadedPath) {
                        $ticket->attachments()->create([
                            'file_name' => $fileName,
                            'file_path' => $downloadedPath,
                            'file_type' => $attachment['type'],
                            'telegram_file_id' => $attachment['file_id'],
                        ]);
                    }
                }
            }

            // Уведомляем пользователя через редактирование сообщения
            $attachmentText = count($attachments) > 0
                ? "\n📎 Прикреплено файлов: " . count($attachments)
                : '';

            if ($session->hasWizardMessage()) {
                $this->bot->editMessage(
                    $chatId,
                    $session->getMessageId(),
                    "✅ <b>Заявка #{$ticket->id} успешно создана!</b>{$attachmentText}\n\n" .
                    "🎉 Ваша заявка принята в обработку.\n" .
                    "📊 Вы можете отслеживать её статус в системе.\n\n" .
                    "Используйте /start для создания новой заявки."
                );
            }

            // Очищаем сессию
            $session->clearData();

        } catch (\Exception $e) {
            if ($session->hasWizardMessage()) {
                $this->bot->editMessage(
                    $chatId,
                    $session->getMessageId(),
                    "❌ <b>Ошибка при создании заявки</b>\n\n" .
                    "Попробуйте ещё раз или обратитесь к администратору.\n\n" .
                    "Используйте /start чтобы начать заново."
                );
            }

            Log::error('Ошибка создания тикета из Telegram', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $session->clearData();
        }
    }

    /**
     * Отменить создание заявки
     */
    public function cancel(TelegramSession $session, int $chatId): void
    {
        // Редактируем сообщение wizard'а на финальное
        if ($session->hasWizardMessage()) {
            $this->bot->editMessage(
                $chatId,
                $session->getMessageId(),
                "❌ <b>Создание заявки отменено</b>\n\n" .
                "Используйте /start чтобы начать заново."
            );
        }

        $session->clearData();
    }
}
