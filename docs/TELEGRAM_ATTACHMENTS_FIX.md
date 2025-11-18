# 🔧 Исправление отправки вложений в Telegram боте

## 📋 Проблема

При нажатии кнопки "Подробнее" в Telegram боте вложения к заявке не отправлялись, если заявка была создана через веб-интерфейс.

## 🔍 Причина

**Два способа хранения вложений:**

1. **Telegram file_id** - для заявок созданных через Telegram
   - Хранится в поле `telegram_file_id`
   - Отправляется моментально (уже в Telegram)

2. **Файл на сервере** - для заявок созданных через веб
   - Хранится в `storage/app/public/`
   - Поле `file_path` содержит относительный путь
   - НЕ имеет `telegram_file_id`

**Старая логика:**
```php
if ($attachment->telegram_file_id) {
    // Отправляем
}
// НЕТ FALLBACK для file_path!
```

## ✅ Решение

Добавлен **fallback** для отправки файлов по публичному URL:

```php
if ($attachment->telegram_file_id) {
    // Приоритет: используем telegram_file_id (быстрее)
    $this->bot->sendPhoto($chatId, $attachment->telegram_file_id, $caption);
} else if ($attachment->file_path) {
    // Fallback: отправляем по публичному URL
    $filePath = storage_path('app/public/' . $attachment->file_path);

    if (file_exists($filePath)) {
        $publicUrl = url('storage/' . $attachment->file_path);
        $this->bot->sendPhoto($chatId, $publicUrl, $caption);
    }
}
```

---

## ⚙️ Требования для работы

### 1. Символическая ссылка storage

Для доступа к файлам через `/storage/` URL нужна символическая ссылка:

```bash
php artisan storage:link
```

Эта команда создает:
```
public/storage -> ../storage/app/public
```

### 2. Правильный APP_URL в .env

```env
APP_URL=http://10.193.0.55:8041
# или
APP_URL=https://yourdomain.com
```

Функция `url()` использует `APP_URL` для генерации полных URL.

---

## 🔄 Как работает

### Сценарий 1: Заявка через Telegram

```
1. Пользователь отправляет фото в Telegram
2. Сохраняется telegram_file_id = "AgACAgIAAxkBAAIC..."
3. При просмотре используется telegram_file_id
4. Быстро ✅
```

### Сценарий 2: Заявка через веб

```
1. Пользователь загружает фото через форму
2. Файл сохраняется: storage/app/public/tickets/photo_123.jpg
3. file_path = "tickets/photo_123.jpg"
4. telegram_file_id = NULL
5. При просмотре:
   - Генерируется URL: http://10.193.0.55:8041/storage/tickets/photo_123.jpg
   - Telegram скачивает по URL
   - Отправляет пользователю
6. Работает ✅
```

---

## 📝 Изменённый код

**Файл:** `app/Services/Telegram/Handlers/CallbackQueryHandler.php`

**Метод:** `handleViewTicket()`

**Строки:** 239-280

```php
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
                // ✅ НОВОЕ: Fallback для веб-вложений
                $filePath = storage_path('app/public/' . $attachment->file_path);

                if (file_exists($filePath)) {
                    // Генерируем публичный URL
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
```

---

## 🧪 Тестирование

### На сервере в контейнере:

```bash
# 1. Проверим символическую ссылку
sudo docker exec vverp_app ls -la public/storage

# Должно быть:
# lrwxrwxrwx 1 appuser appgroup 24 Nov 18 12:00 storage -> ../storage/app/public

# 2. Если ссылки нет, создаём:
sudo docker exec vverp_app php artisan storage:link

# 3. Проверим доступность файла через URL
curl http://10.193.0.55:8041/storage/tickets/some_file.jpg

# 4. Проверим APP_URL в .env
sudo docker exec vverp_app php artisan tinker --execute="
echo 'APP_URL: ' . config('app.url') . PHP_EOL;
echo 'Test URL: ' . url('storage/test.jpg') . PHP_EOL;
"
```

### Тест отправки вложения:

```bash
sudo docker exec vverp_app php artisan tinker --execute="
\$ticket = \App\Models\Ticket::with('attachments')->first();
if (\$ticket && \$ticket->attachments->count() > 0) {
    \$attachment = \$ticket->attachments->first();
    echo 'Attachment ID: ' . \$attachment->id . PHP_EOL;
    echo 'Type: ' . \$attachment->file_type . PHP_EOL;
    echo 'Telegram File ID: ' . (\$attachment->telegram_file_id ?? 'NULL') . PHP_EOL;
    echo 'File Path: ' . (\$attachment->file_path ?? 'NULL') . PHP_EOL;

    if (\$attachment->file_path) {
        \$fullPath = storage_path('app/public/' . \$attachment->file_path);
        echo 'Full Path: ' . \$fullPath . PHP_EOL;
        echo 'File Exists: ' . (file_exists(\$fullPath) ? 'YES ✅' : 'NO ❌') . PHP_EOL;
        echo 'Public URL: ' . url('storage/' . \$attachment->file_path) . PHP_EOL;
    }
}
"
```

---

## 🔍 Логирование

### Успешная отправка:
```
[info] Сообщение отправлено {"chat_id": 123456, "message_id": 789}
```

### Файл не найден:
```
[warning] Attachment file not found {
    "ticket_id": 1,
    "attachment_id": 5,
    "file_path": "/var/www/storage/app/public/tickets/photo.jpg"
}
```

### Ошибка отправки:
```
[error] Failed to send attachment {
    "ticket_id": 1,
    "attachment_id": 5,
    "error": "Telegram API Error: Bad Request: wrong file identifier/HTTP URL specified"
}
```

---

## ⚠️ Важные моменты

### 1. Безопасность

- Файлы доступны по публичному URL
- Используйте `.gitignore` для `storage/app/public/*`
- Рассмотрите добавление авторизации для чувствительных файлов

### 2. Производительность

- **telegram_file_id** - мгновенно (файл уже в Telegram)
- **URL** - Telegram скачивает файл (~1-3 сек)
- Для больших файлов может быть задержка

### 3. Лимиты Telegram

- **Фото:** до 10 МБ
- **Видео:** до 50 МБ
- **Документы:** до 50 МБ
- При превышении - ошибка API

---

## 🚀 Развёртывание

### На production сервере:

```bash
cd /home/erp/vverp

# 1. Pull изменений
git pull

# 2. Создать символическую ссылку (если ещё нет)
sudo docker exec vverp_app php artisan storage:link

# 3. Проверить .env
sudo docker exec vverp_app grep APP_URL .env

# 4. Очистить кэш
sudo docker exec vverp_app php artisan cache:clear

# 5. Проверить доступность файлов
curl http://10.193.0.55:8041/storage/
```

---

## ✅ Проверка работы

1. **Создайте заявку через веб** с фото
2. **Откройте Telegram бот**
3. **Найдите заявку** (получите уведомление или через список)
4. **Нажмите "Подробнее"**
5. **Проверьте:** Должны прийти:
   - Текст с деталями заявки
   - Фото/видео/документы

---

## 📚 Связанные документы

- [TELEGRAM_ATTACHMENTS_IN_DETAILS.md](./TELEGRAM_ATTACHMENTS_IN_DETAILS.md) - Оригинальная документация про вложения
- [TELEGRAM_BOT.md](./TELEGRAM_BOT.md) - Общая документация по боту
- [QUICK_START_WORKFLOW.md](./QUICK_START_WORKFLOW.md) - Workflow заявок

---

**Статус:** ✅ Исправлено
**Дата:** 18.11.2024
**Версия:** v1.0
