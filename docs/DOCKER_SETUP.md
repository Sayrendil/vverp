# Docker Setup для VkusVill ERP

## Архитектура

Проект состоит из следующих контейнеров:

1. **app** (`vverp_app`) - PHP-FPM для обработки веб-запросов
2. **nginx** (`vverp_nginx`) - Веб-сервер на порту **80**
3. **db** (`vverp_db`) - MySQL 8.0 база данных
4. **queue** (`vverp_queue`) - Queue Worker + Telegram Bot (с Supervisor)
5. **node** (`vverp_node`) - Vite dev server для фронтенда

## Что делает контейнер `queue`

Контейнер **vverp_queue** запускает 2 процесса через Supervisor:

1. **laravel-worker** (2 процесса) - обработка очередей Laravel
   - Команда: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`
   - Логи: `storage/logs/worker.log`
   - Автоматический перезапуск при ошибках

2. **telegram-bot** (1 процесс) - Telegram бот polling
   - Команда: `php artisan telegram:polling`
   - Логи: `storage/logs/telegram-bot.log`
   - Автоматический перезапуск при ошибках

## Запуск проекта

### 1. Первый запуск (сборка контейнеров)

```bash
# Остановите все контейнеры (если запущены)
docker-compose down

# Пересоберите контейнеры
docker-compose build

# Запустите все сервисы
docker-compose up -d
```

### 2. Проверка статуса

```bash
# Проверить запущенные контейнеры
docker-compose ps

# Логи всех контейнеров
docker-compose logs -f

# Логи конкретного контейнера
docker-compose logs -f queue
docker-compose logs -f app
docker-compose logs -f nginx
```

### 3. Проверка Queue Worker и Telegram Bot

```bash
# Войти в контейнер queue
docker exec -it vverp_queue bash

# Проверить статус supervisor
supervisorctl status

# Перезапустить конкретный процесс
supervisorctl restart laravel-worker:*
supervisorctl restart telegram-bot

# Остановить/запустить
supervisorctl stop telegram-bot
supervisorctl start telegram-bot

# Посмотреть логи
tail -f /var/www/storage/logs/worker.log
tail -f /var/www/storage/logs/telegram-bot.log
```

## Доступ к приложению

- **Веб-интерфейс**: http://localhost (порт 80)
- **MySQL**: localhost:33061 (порт 33061)
- **Vite dev server**: localhost:5175 (порт 5175)

## Обновление конфигурации

### После изменения кода

```bash
# Контейнеры с volume монтированием автоматически видят изменения
# Но для queue worker нужно перезапустить процессы:
docker exec -it vverp_queue supervisorctl restart all
```

### После изменения docker-compose.yml или Dockerfile

```bash
# Пересоберите конкретный контейнер
docker-compose build queue

# Перезапустите
docker-compose up -d queue
```

### После изменения supervisord.conf

```bash
# Перезапустите контейнер
docker-compose restart queue

# Или
docker exec -it vverp_queue supervisorctl reread
docker exec -it vverp_queue supervisorctl update
```

## Остановка и удаление

```bash
# Остановить все контейнеры
docker-compose down

# Остановить и удалить volumes (БД будет очищена!)
docker-compose down -v
```

## Troubleshooting

### Queue Worker не запускается

```bash
# Проверьте логи
docker-compose logs queue

# Проверьте права на storage
docker exec -it vverp_queue ls -la /var/www/storage/logs

# Создайте директории вручную
docker exec -it vverp_queue mkdir -p /var/www/storage/logs
docker exec -it vverp_queue chown -R www-data:www-data /var/www/storage
```

### Telegram Bot не отвечает

```bash
# Проверьте статус
docker exec -it vverp_queue supervisorctl status telegram-bot

# Посмотрите логи
docker exec -it vverp_queue tail -50 /var/www/storage/logs/telegram-bot.log

# Перезапустите
docker exec -it vverp_queue supervisorctl restart telegram-bot
```

### Порт 80 уже занят

```bash
# Найдите процесс на порту 80
sudo lsof -i :80

# Остановите процесс или измените порт в docker-compose.yml:
# ports:
#   - "8080:80"  # измените на другой порт
```

## Production рекомендации

1. **Увеличьте количество worker процессов** в `supervisord.conf`:
   ```
   numprocs=4  # вместо 2
   ```

2. **Настройте логротацию** для `storage/logs/*.log`

3. **Используйте Redis** вместо database driver для очередей:
   ```bash
   # Добавьте в docker-compose.yml:
   redis:
     image: redis:alpine
     container_name: vverp_redis
     networks:
       - laravel

   # Измените QUEUE_CONNECTION в .env:
   QUEUE_CONNECTION=redis
   ```

4. **Мониторинг**: Используйте Laravel Horizon для мониторинга очередей
