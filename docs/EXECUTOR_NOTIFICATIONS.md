# 📨 Уведомления исполнителям через Telegram

## Обзор

Система автоматически отправляет уведомления всем активным исполнителям категории при создании новой заявки.

## Архитектура

### Компоненты

1. **Event**: `TicketCreated` - срабатывает при создании заявки
2. **Listener**: `SendTicketCreatedNotificationToExecutors` - обрабатывает событие
3. **Service**: `TelegramBotService` - отправляет сообщения в Telegram
4. **Queue**: `notifications` - асинхронная обработка

### Схема работы

```
Создание заявки
    ↓
Event: TicketCreated
    ↓
Listener: SendTicketCreatedNotificationToExecutors (в очереди)
    ↓
Поиск активных исполнителей категории с telegram_id
    ↓
Отправка уведомлений каждому исполнителю
    ↓
Логирование результатов
```

## Условия отправки

Уведомления отправляются **только** исполнителям, которые:

1. ✅ Добавлены в категорию заявки (таблица `category_executors`)
2. ✅ Активны (`is_active = true`)
3. ✅ Имеют заполненный `telegram_id`

## Формат уведомления

```
🆕 Новая заявка #123

📁 Категория: IT поддержка
🏪 Магазин: ВкусВилл Центральный
❗ Проблема: Не работает касса
👤 Автор: Иванов Иван
📊 Статус: Создана

📝 Заголовок:
Касса №3 не включается

📄 Описание:
После перезагрузки касса не реагирует на кнопки

⏰ Создана: 17.11.2024 14:30
```

## Управление исполнителями

### Добавление исполнителя в категорию

**Интерфейс**: `/executors` (только для администраторов)

**API**: `POST /api/executors`
```json
{
  "user_id": 5,
  "category_id": 2,
  "priority": 5,
  "max_tickets": 10
}
```

### Активация/деактивация исполнителя

**API**: `PATCH /api/executors/{userId}/categories/{categoryId}/toggle`

- При деактивации (`is_active = false`) исполнитель перестает получать уведомления
- Уже назначенные заявки остаются за ним

### Привязка Telegram ID

**Способ 1**: Автоматически при создании заявки через бота
- Пользователь запускает `/start` в боте
- Система автоматически сохраняет `telegram_id`

**Способ 2**: Вручную через БД (для тестирования)
```sql
UPDATE users SET telegram_id = '123456789' WHERE id = 5;
```

## Настройка очереди

### Обработка очереди уведомлений

```bash
# Запуск worker для очереди notifications
php artisan queue:work --queue=notifications --tries=3 --timeout=120

# С использованием Supervisor (production)
[program:vverp-queue-notifications]
command=php /path/to/artisan queue:work --queue=notifications --tries=3 --timeout=120
autostart=true
autorestart=true
user=www-data
```

### Конфигурация

**Файл**: `app/Listeners/SendTicketCreatedNotificationToExecutors.php`

```php
public string $queue = 'notifications';   // Очередь
public int $tries = 3;                     // Попытки
public int $timeout = 120;                 // Таймаут (сек)
```

## Тестирование

### Команда для тестирования

```bash
# Тест с последней заявкой
php artisan test:executor-notifications

# Тест с конкретной заявкой
php artisan test:executor-notifications 123
```

Команда:
1. Находит заявку
2. Показывает категорию
3. Выводит список исполнителей с telegram_id
4. Запрашивает подтверждение
5. Отправляет тестовые уведомления

### Ручное тестирование

```bash
# 1. Запустить queue worker в отдельной консоли
php artisan queue:work --queue=notifications

# 2. В другой консоли создать заявку через Tinker
php artisan tinker
>>> $ticket = App\Models\Ticket::factory()->create(['ticket_category_id' => 2]);
>>> event(new App\Events\TicketCreated($ticket));
```

### Проверка логов

```bash
# Следить за логами в реальном времени
tail -f storage/logs/laravel.log | grep "executor"

# Поиск ошибок
grep "Failed to send notification to executor" storage/logs/laravel.log
```

## Логирование

Система логирует все этапы отправки:

### Успешная отправка
```
[info] Sending notifications to executors
  ticket_id: 123
  category_id: 2
  executors_count: 3

[info] Notification sent to executor
  ticket_id: 123
  executor_id: 5
  executor_name: Петров Петр
```

### Ошибки отправки
```
[error] Failed to send notification to executor
  ticket_id: 123
  executor_id: 5
  executor_name: Петров Петр
  error: Telegram API error: chat not found
```

### Нет исполнителей
```
[info] No executors with telegram_id found for category
  ticket_id: 123
  category_id: 2
```

## Troubleshooting

### Уведомления не приходят

**1. Проверьте наличие исполнителей**
```sql
SELECT u.id, u.name, u.telegram_id, ce.is_active
FROM users u
JOIN category_executors ce ON u.id = ce.user_id
WHERE ce.ticket_category_id = 2
  AND ce.is_active = true
  AND u.telegram_id IS NOT NULL;
```

**2. Проверьте очередь**
```bash
# Есть ли задачи в очереди?
php artisan queue:monitor notifications

# Запущен ли worker?
ps aux | grep "queue:work"
```

**3. Проверьте Telegram бота**
```bash
# Бот работает?
ps aux | grep "telegram:polling"

# Логи бота
grep "Telegram" storage/logs/laravel.log
```

### Дублирование уведомлений

Проверьте, что в `EventServiceProvider` listener добавлен **один раз**:
```php
TicketCreated::class => [
    SendTicketCreatedTelegramNotification::class,      // Автору
    SendTicketCreatedNotificationToExecutors::class,   // Исполнителям
],
```

### Исполнитель не получает уведомления

1. **Проверьте telegram_id**:
   ```sql
   SELECT id, name, telegram_id FROM users WHERE id = 5;
   ```

2. **Проверьте активность**:
   ```sql
   SELECT * FROM category_executors
   WHERE user_id = 5 AND ticket_category_id = 2;
   ```

3. **Проверьте, что пользователь начал чат с ботом**:
   - Пользователь должен отправить `/start` боту
   - Только тогда бот сможет отправлять ему сообщения

## Расширение функционала

### Добавить уведомления о назначении

Создать listener `SendTicketAssignedTelegramNotification`:

```php
// app/Events/TicketAssigned.php уже существует

// Добавить в EventServiceProvider:
TicketAssigned::class => [
    SendTicketAssignedTelegramNotification::class,
],
```

### Добавить кнопки для взятия в работу

Модифицировать `formatMessage()` в listener:

```php
private function formatMessage($ticket): array
{
    $text = "🆕 Новая заявка...";

    return [
        'text' => $text,
        'reply_markup' => [
            'inline_keyboard' => [[
                ['text' => '✅ Взять в работу', 'callback_data' => "assign:{$ticket->id}"],
                ['text' => '👁 Посмотреть', 'callback_data' => "view:{$ticket->id}"],
            ]]
        ]
    ];
}
```

### Настроить приоритеты уведомлений

Использовать поле `priority` из `category_executors`:

```php
$executors = $ticket->ticketCategory
    ->activeExecutors()
    ->whereNotNull('telegram_id')
    ->orderByPivot('priority', 'desc')  // Сначала более приоритетным
    ->get();
```

## Production рекомендации

1. **Используйте Redis** вместо database для очередей:
   ```env
   QUEUE_CONNECTION=redis
   ```

2. **Настройте Supervisor** для автозапуска workers

3. **Мониторинг очередей**:
   - Laravel Horizon (для Redis)
   - Или custom dashboard

4. **Rate limiting** для Telegram API:
   - Не более 30 сообщений/секунду
   - Группируйте отправку если много исполнителей

5. **Webhook вместо polling** (см. память о будущих улучшениях)
