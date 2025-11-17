<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use App\Services\TelegramWizardService;
use App\Services\Telegram\TicketWorkflowService;
use App\Services\Telegram\UpdateDispatcher;
use App\Services\Telegram\Handlers\CommandHandler;
use App\Services\Telegram\Handlers\CallbackQueryHandler;
use App\Services\Telegram\Handlers\MediaHandler;
use App\Services\Telegram\Handlers\TextMessageHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Команда для запуска Telegram бота в режиме Long Polling
 */
class TelegramBotPolling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:polling
                            {--timeout=30 : Таймаут запроса в секундах}
                            {--limit=100 : Максимальное количество обновлений за раз}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запуск Telegram бота в режиме Long Polling для получения обновлений';

    protected TelegramBotService $telegramBot;
    protected UpdateDispatcher $dispatcher;
    protected int $offset = 0;
    protected bool $running = true;
    protected int $consecutiveErrors = 0;
    protected const MAX_CONSECUTIVE_ERRORS = 10;

    public function __construct(
        TelegramBotService $telegramBot,
        TelegramWizardService $wizard,
        TicketWorkflowService $workflow
    ) {
        parent::__construct();
        $this->telegramBot = $telegramBot;

        // Инициализация диспетчера с обработчиками
        $this->dispatcher = new UpdateDispatcher();

        // Регистрируем обработчики в порядке приоритета
        // Команды имеют наивысший приоритет (используют роутер)
        $this->dispatcher->registerHandler(new CommandHandler($telegramBot));
        // Callback queries (кнопки)
        $this->dispatcher->registerHandler(new CallbackQueryHandler($telegramBot, $wizard, $workflow));
        // Медиа-файлы
        $this->dispatcher->registerHandler(new MediaHandler($telegramBot, $wizard));
        // Текстовые сообщения (последний приоритет)
        $this->dispatcher->registerHandler(new TextMessageHandler($telegramBot, $wizard));
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🤖 Запуск Telegram бота...');

        // Проверка токена и соединения
        $botInfo = $this->telegramBot->getMe();

        if (!$botInfo) {
            $this->error('❌ Не удалось подключиться к Telegram API');
            $this->error('Проверьте TELEGRAM_BOT_TOKEN в .env файле');
            return Command::FAILURE;
        }

        $this->info('✅ Подключено к боту: @' . ($botInfo['username'] ?? 'unknown'));
        $this->info('📛 Имя: ' . ($botInfo['first_name'] ?? 'unknown'));
        $this->info('📦 Зарегистрировано обработчиков: ' . $this->dispatcher->getHandlersCount());
        $this->info('Для остановки нажмите Ctrl+C');
        $this->newLine();

        // Обработка сигнала для корректной остановки
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, function () {
                $this->running = false;
                $this->newLine();
                $this->info('🛑 Остановка бота...');
            });
        }

        // Основной цикл получения и обработки обновлений
        while ($this->running) {
            try {
                // Получаем обновления
                $updates = $this->telegramBot->getUpdates($this->offset);

                if (empty($updates)) {
                    // Сбрасываем счетчик ошибок при успешном запросе
                    $this->consecutiveErrors = 0;
                    continue;
                }

                // Обрабатываем каждое обновление
                foreach ($updates as $update) {
                    $this->dispatcher->dispatch($update);

                    // Обновляем offset для следующего запроса
                    $this->offset = $update['update_id'] + 1;
                }

                // Сбрасываем счетчик ошибок при успешной обработке
                $this->consecutiveErrors = 0;

            } catch (\Exception $e) {
                $this->consecutiveErrors++;

                $this->error("❌ Ошибка [{$this->consecutiveErrors}/" . self::MAX_CONSECUTIVE_ERRORS . "]: " . $e->getMessage());
                Log::error('Telegram Bot Polling Error: ' . $e->getMessage(), [
                    'exception' => $e,
                    'consecutive_errors' => $this->consecutiveErrors,
                ]);

                // Проверяем превышение лимита последовательных ошибок
                if ($this->consecutiveErrors >= self::MAX_CONSECUTIVE_ERRORS) {
                    $this->error('🛑 Слишком много последовательных ошибок. Остановка бота.');
                    $this->error('Проверьте логи и конфигурацию, затем перезапустите бот.');
                    return Command::FAILURE;
                }

                // Exponential backoff: 5s, 10s, 20s, 40s, 80s, ..., max 300s (5 min)
                $backoff = min(300, 5 * pow(2, $this->consecutiveErrors - 1));
                $this->warn("⏳ Повтор через {$backoff} секунд...");
                sleep((int)$backoff);
            }
        }

        $this->info('👋 Бот остановлен');
        return Command::SUCCESS;
    }
}
