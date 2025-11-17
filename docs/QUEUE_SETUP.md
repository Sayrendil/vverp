# Настройка Queue для Telegram бота

## 📦 Установка

Очереди уже встроены в Laravel. Нужно только настроить драйвер.

### 1. Настройка драйвера

Откройте `.env` и выберите драйвер:

```env
# Для разработки (простой, но без persistence)
QUEUE_CONNECTION=sync

# Для production (рекомендуется)
QUEUE_CONNECTION=database

# Или Redis (лучшая производительность)
QUEUE_CONNECTION=redis
```

### 2. Для database драйвера

```bash
# Создать таблицы
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

### 3. Для Redis драйвера

```bash
# Установить пакет
composer require predis/predis

# В .env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## 🚀 Использование

### Базовое использование

```php
use App\Services\Telegram\TelegramQueueService;

// Отправить сообщение
TelegramQueueService::sendMessage($chatId, 'Привет!');

// С кнопками
TelegramQueueService::sendMessageWithButtons($chatId, 'Выберите:', [
    [['text' => 'Кнопка 1', 'callback_data' => 'btn1']],
    [['text' => 'Кнопка 2', 'callback_data' => 'btn2']],
]);

// С задержкой (5 секунд)
TelegramQueueService::sendMessage($chatId, 'Через 5 сек', [], 5);

// Срочное (в приоритетную очередь)
TelegramQueueService::sendUrgent($chatId, 'Важно!');
```

### Массовая рассылка

```php
// Рассылка с задержкой 2 секунды между сообщениями
$recipients = [
    ['chat_id' => 123, 'text' => 'Сообщение 1'],
    ['chat_id' => 456, 'text' => 'Сообщение 2'],
    ['chat_id' => 789, 'text' => 'Сообщение 3'],
];

TelegramQueueService::sendBulk($recipients, delayBetween: 2);
```

### Прямое использование Jobs

```php
use App\Jobs\SendTelegramMessage;

// Простая отправка
SendTelegramMessage::dispatch($chatId, $text);

// С настройками
SendTelegramMessage::dispatch($chatId, $text)
    ->onQueue('telegram-urgent')    // Приоритетная очередь
    ->delay(now()->addMinutes(5))   // Задержка 5 минут
    ->afterResponse();              // Отправить после ответа пользователю
```

---

## ⚙️ Запуск Queue Workers

### Development (один worker)

```bash
php artisan queue:work --queue=telegram,telegram-urgent,notifications,default
```

### Production (Supervisor)

См. файл `/docs/SUPERVISOR.md`

---

## 📊 Мониторинг очереди

### Проверить количество задач

```bash
# В очереди
php artisan queue:monitor telegram telegram-urgent notifications

# Failed jobs
php artisan queue:failed
```

### Laravel Horizon (для Redis)

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

Откройте: `http://your-domain.com/horizon`

---

## 🔧 Обработка ошибок

### Просмотр failed jobs

```bash
# Список
php artisan queue:failed

# Повторить все
php artisan queue:retry all

# Повторить конкретную
php artisan queue:retry <job-id>

# Очистить failed
php artisan queue:flush
```

### Автоматический retry

Jobs уже настроены с автоматическими повторами:
- 3 попытки
- Задержка: 10, 30, 60 секунд
- Логирование всех ошибок

---

## ⚡ Оптимизация производительности

### Несколько workers

```bash
# Запустить 3 worker'а параллельно
php artisan queue:work --queue=telegram --tries=3 &
php artisan queue:work --queue=telegram --tries=3 &
php artisan queue:work --queue=telegram --tries=3 &
```

### Приоритеты очередей

```bash
# Сначала обрабатывать urgent, потом telegram, потом остальные
php artisan queue:work --queue=telegram-urgent,telegram,notifications,default
```

### Timeout и память

```bash
php artisan queue:work \
  --timeout=60 \          # Максимальное время выполнения job
  --memory=512 \          # Максимальная память
  --sleep=3 \             # Задержка между проверками (секунды)
  --tries=3               # Количество попыток
```

---

## 📝 Примеры использования

### В контроллере

```php
class TicketController extends Controller
{
    public function store(Request $request)
    {
        $ticket = Ticket::create($request->validated());

        // Отправляем уведомление асинхронно
        TelegramQueueService::sendMessage(
            $ticket->user->telegram_id,
            "Заявка #{$ticket->id} создана"
        );

        // Пользователь сразу получает ответ
        return redirect()->route('tickets.show', $ticket);
    }
}
```

### В Event Listener

```php
class SendTicketCreatedNotification implements ShouldQueue
{
    public function handle(TicketCreated $event)
    {
        // Listener сам в очереди + отправка в очереди = двойная асинхронность
        TelegramQueueService::sendMessage(
            $event->ticket->user->telegram_id,
            $this->formatMessage($event->ticket)
        );
    }
}
```

### В Command

```php
class SendDailyReport extends Command
{
    public function handle()
    {
        $admins = User::whereNotNull('telegram_id')->get();

        $messages = $admins->map(fn($admin) => [
            'chat_id' => $admin->telegram_id,
            'text' => $this->generateReport($admin),
        ])->toArray();

        // Массовая рассылка с задержкой 2 сек между сообщениями
        TelegramQueueService::sendBulk($messages, 2);

        $this->info("Queued {$admins->count()} reports");
    }
}
```

---

## 🎯 Best Practices

1. **Всегда используйте Queue для отправки сообщений в production**
   ```php
   // ❌ Плохо (синхронно)
   $bot->sendMessage($chatId, $text);

   // ✅ Хорошо (асинхронно)
   TelegramQueueService::sendMessage($chatId, $text);
   ```

2. **Используйте разные очереди для разных приоритетов**
   ```php
   // Срочные уведомления
   ->onQueue('telegram-urgent')

   // Обычные
   ->onQueue('telegram')

   // Рассылки
   ->onQueue('telegram-bulk')
   ```

3. **Добавляйте задержки для массовых рассылок**
   ```php
   // Соблюдение лимитов API (30 msg/sec)
   TelegramQueueService::sendBulk($recipients, delayBetween: 1);
   ```

4. **Мониторьте failed jobs**
   ```bash
   # Настроить уведомления при failed jobs
   php artisan queue:failed --watch
   ```

5. **Используйте теги для мониторинга**
   ```php
   // В Job уже настроены теги
   public function tags(): array {
       return ['telegram', 'chat:' . $this->chatId];
   }
   ```

---

## 🐛 Troubleshooting

### Задачи не выполняются

```bash
# Проверить запущен ли worker
ps aux | grep "queue:work"

# Проверить настройки
php artisan config:clear
php artisan queue:restart
```

### Задачи постоянно падают

```bash
# Посмотреть логи
tail -f storage/logs/laravel.log

# Посмотреть failed jobs
php artisan queue:failed
```

### Медленная обработка

```bash
# Запустить больше workers
# Или увеличить timeout
php artisan queue:work --timeout=120
```

---

## 📚 Дополнительно

- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Horizon](https://laravel.com/docs/horizon) (для Redis)
- [Supervisor Configuration](/docs/SUPERVISOR.md)
