# Supervisor - Конфигурация и управление

## 📦 Что такое Supervisor?

Supervisor - это менеджер процессов для Linux. Он:
- ✅ Автоматически запускает процессы при старте сервера
- ✅ Перезапускает процессы при падении
- ✅ Управляет логами
- ✅ Позволяет запускать несколько процессов параллельно

---

## 🔧 Установка

### Ubuntu/Debian

```bash
sudo apt update
sudo apt install supervisor
```

### CentOS/RHEL

```bash
sudo yum install supervisor
sudo systemctl enable supervisord
sudo systemctl start supervisord
```

### Проверка установки

```bash
sudo supervisorctl version
```

---

## ⚙️ Конфигурация для Telegram бота

### 1. Копируем файлы конфигурации

```bash
# Из репозитория в Supervisor
sudo cp supervisor/*.conf /etc/supervisor/conf.d/

# Или создаем вручную (см. ниже)
```

### 2. Конфигурации

#### **telegram-bot.conf** - Сам бот

```ini
[program:telegram-bot]
process_name=%(program_name)s
command=php /var/www/vverp/artisan telegram:polling
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/vverp/storage/logs/telegram-bot.log
stopwaitsecs=3600
```

**Параметры:**
- `command` - команда запуска (измените путь!)
- `autostart=true` - запускать при старте Supervisor
- `autorestart=true` - перезапускать при падении
- `user=www-data` - от какого пользователя запускать
- `numprocs=1` - один процесс бота
- `stopwaitsecs=3600` - ждать час перед принудительным завершением

#### **telegram-queue.conf** - Queue workers

```ini
[program:telegram-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/vverp/artisan queue:work --queue=telegram,notifications --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/vverp/storage/logs/telegram-queue.log
stopwaitsecs=3600
```

**Параметры:**
- `numprocs=2` - 2 worker'а параллельно
- `--queue=telegram,notifications` - какие очереди обрабатывать
- `--sleep=3` - задержка 3 сек между проверками
- `--tries=3` - 3 попытки при ошибке
- `--max-time=3600` - перезапускаться каждый час

#### **telegram-queue-urgent.conf** - Срочная очередь

```ini
[program:telegram-queue-urgent]
process_name=%(program_name)s
command=php /var/www/vverp/artisan queue:work --queue=telegram-urgent --sleep=1 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
stdout_logfile=/var/www/vverp/storage/logs/telegram-queue-urgent.log
stopwaitsecs=3600
```

**Особенности:**
- `--sleep=1` - проверяет очередь каждую секунду (приоритет!)
- `numprocs=1` - один dedicated worker

---

## 🚀 Запуск и управление

### Первый запуск

```bash
# 1. Обновить конфигурацию
sudo supervisorctl reread

# 2. Добавить новые программы
sudo supervisorctl update

# 3. Проверить статус
sudo supervisorctl status
```

Вывод должен быть:
```
telegram-bot                     RUNNING   pid 12345, uptime 0:00:10
telegram-queue:telegram-queue_00 RUNNING   pid 12346, uptime 0:00:10
telegram-queue:telegram-queue_01 RUNNING   pid 12347, uptime 0:00:10
telegram-queue-urgent            RUNNING   pid 12348, uptime 0:00:10
```

### Управление процессами

```bash
# Запустить все
sudo supervisorctl start all

# Запустить конкретную программу
sudo supervisorctl start telegram-bot
sudo supervisorctl start telegram-queue:*

# Остановить
sudo supervisorctl stop telegram-bot
sudo supervisorctl stop all

# Перезапустить
sudo supervisorctl restart telegram-bot
sudo supervisorctl restart all

# Статус
sudo supervisorctl status

# Логи (в реальном времени)
sudo supervisorctl tail -f telegram-bot
sudo supervisorctl tail -f telegram-queue stdout
```

### После изменения кода

```bash
# Перезапустить все процессы
sudo supervisorctl restart all

# Или только бота
sudo supervisorctl restart telegram-bot

# Для queue - нужен restart чтобы подгрузить новый код
sudo supervisorctl restart telegram-queue:*
```

### После изменения конфига

```bash
# Перечитать конфиги
sudo supervisorctl reread

# Применить изменения
sudo supervisorctl update

# Перезапустить
sudo supervisorctl restart all
```

---

## 📊 Мониторинг

### Просмотр логов

```bash
# Telegram бот
tail -f /var/www/vverp/storage/logs/telegram-bot.log

# Queue workers
tail -f /var/www/vverp/storage/logs/telegram-queue.log

# Срочная очередь
tail -f /var/www/vverp/storage/logs/telegram-queue-urgent.log

# Через supervisorctl
sudo supervisorctl tail -f telegram-bot
sudo supervisorctl tail -f telegram-queue stdout
```

### Проверка статуса

```bash
# Статус всех процессов
sudo supervisorctl status

# Детальная информация
sudo supervisorctl status telegram-bot
```

### Web интерфейс (опционально)

Добавьте в `/etc/supervisor/supervisord.conf`:

```ini
[inet_http_server]
port=*:9001
username=admin
password=your_secure_password
```

Перезапустите Supervisor:
```bash
sudo systemctl restart supervisor
```

Откройте в браузере: `http://your-server:9001`

---

## 🐛 Troubleshooting

### Процессы не запускаются

```bash
# Проверить синтаксис конфига
sudo supervisorctl reread

# Посмотреть ошибки
sudo supervisorctl tail telegram-bot stderr

# Проверить права
ls -la /var/www/vverp/artisan
sudo chown -R www-data:www-data /var/www/vverp/storage
```

### Процессы постоянно падают

```bash
# Смотрим логи
sudo supervisorctl tail -f telegram-bot stderr

# Проверяем может ли пользователь www-data запустить
sudo -u www-data php /var/www/vverp/artisan telegram:polling

# Проверяем зависимости
cd /var/www/vverp
composer install --no-dev
php artisan config:cache
```

### Процессы не останавливаются

```bash
# Принудительно убить
sudo supervisorctl stop telegram-bot
sudo pkill -f "telegram:polling"

# Или через supervisorctl
sudo supervisorctl shutdown
sudo systemctl restart supervisor
```

### Логи не пишутся

```bash
# Создать директорию для логов
sudo mkdir -p /var/www/vverp/storage/logs
sudo chown -R www-data:www-data /var/www/vverp/storage

# Проверить права
ls -la /var/www/vverp/storage/logs/
```

---

## ⚡ Оптимизация

### Увеличить количество workers

```ini
[program:telegram-queue]
numprocs=5  ; Было 2, стало 5
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
```

### Разделить очереди

```ini
; Worker для telegram
[program:telegram-queue-telegram]
command=php artisan queue:work --queue=telegram
numprocs=3

; Worker для notifications
[program:telegram-queue-notifications]
command=php artisan queue:work --queue=notifications
numprocs=2
```

### Автоперезапуск при утечке памяти

```ini
[program:telegram-queue]
; Перезапускать после обработки 1000 задач
command=php artisan queue:work --max-jobs=1000
```

---

## 🔐 Безопасность

### Минимальные права

```bash
# Убедитесь что пользователь www-data имеет минимальные права
sudo chown -R www-data:www-data /var/www/vverp/storage
sudo chown -R www-data:www-data /var/www/vverp/bootstrap/cache
sudo chmod -R 775 /var/www/vverp/storage
sudo chmod -R 775 /var/www/vverp/bootstrap/cache
```

### Защита Web интерфейса

```ini
[inet_http_server]
port=127.0.0.1:9001  ; Только localhost
username=admin
password=very_secure_password_here_123
```

---

## 📝 Best Practices

1. **Используйте отдельные конфиги** для каждого процесса
2. **Логируйте всё** - используйте `redirect_stderr=true`
3. **Мониторьте** - настройте alerting при падении процессов
4. **Обновляйте код аккуратно:**
   ```bash
   git pull
   composer install --no-dev
   php artisan config:cache
   php artisan route:cache
   sudo supervisorctl restart all
   ```
5. **Делайте backup** конфигураций:
   ```bash
   sudo cp /etc/supervisor/conf.d/*.conf ~/supervisor-backup/
   ```

---

## 📚 Дополнительные ресурсы

- [Supervisor Documentation](http://supervisord.org/)
- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Supervisor Best Practices](http://supervisord.org/running.html#running-supervisorctl)

---

## 🎯 Чеклист установки

- [ ] Установлен Supervisor
- [ ] Скопированы конфиги в `/etc/supervisor/conf.d/`
- [ ] Исправлены пути в конфигах на правильные
- [ ] Создана директория для логов
- [ ] Настроены права для www-data
- [ ] Выполнен `supervisorctl reread && supervisorctl update`
- [ ] Проверен статус: `supervisorctl status`
- [ ] Проверены логи: `tail -f storage/logs/telegram-bot.log`
- [ ] Протестирован restart после git pull
- [ ] Настроен мониторинг/alerting

---

## 💡 Полезные команды

```bash
# Быстрый рестарт всего
alias sr='sudo supervisorctl restart all'

# Проверка статуса
alias ss='sudo supervisorctl status'

# Логи бота
alias tl='tail -f /var/www/vverp/storage/logs/telegram-bot.log'

# Добавьте в ~/.bashrc или ~/.zshrc
```
