# 🚀 Развертывание мониторинга в Docker (vverp)

## Архитектура проекта

```
vverp_app       - Основное PHP приложение
vverp_queue     - Обработчик очередей (здесь выполняются проверки!)
vverp_nginx     - Веб-сервер
vverp_db        - База данных MySQL
```

## ⚠️ ВАЖНО: Проверки выполняются в контейнере `vverp_queue`

Все проверки мониторинга выполняются через Laravel Queue в контейнере **vverp_queue**.
Это значит что **команда `ping` должна быть доступна в этом контейнере!**

## 📋 Пошаговая инструкция развертывания

### Шаг 1: Обновить код на сервере

```bash
# На локальной машине
cd ~/vkusvill/vverp

# Собрать фронтенд
npm run build
tar -czf build.tar.gz -C public build/

# Скопировать на сервер
scp -i ~/.ssh/id_rsa_global build.tar.gz user@10.193.0.55:/home/erp/vverp/

# Скопировать обновленный код
scp -i ~/.ssh/id_rsa_global app/Jobs/CheckHostAvailability.php user@10.193.0.55:/home/erp/vverp/app/Jobs/
scp -i ~/.ssh/id_rsa_global app/Services/MonitoringService.php user@10.193.0.55:/home/erp/vverp/app/Services/
scp -i ~/.ssh/id_rsa_global app/Http/Controllers/MonitoringController.php user@10.193.0.55:/home/erp/vverp/app/Http/Controllers/
scp -i ~/.ssh/id_rsa_global app/Models/HostAvailabilityLog.php user@10.193.0.55:/home/erp/vverp/app/Models/
scp -i ~/.ssh/id_rsa_global app/Models/Host.php user@10.193.0.55:/home/erp/vverp/app/Models/
scp -i ~/.ssh/id_rsa_global routes/web.php user@10.193.0.55:/home/erp/vverp/routes/
scp -i ~/.ssh/id_rsa_global database/migrations/2025_11_19_175722_create_host_availability_logs_table.php user@10.193.0.55:/home/erp/vverp/database/migrations/
```

### Шаг 2: На сервере - распаковать фронтенд

```bash
# Подключиться к серверу
ssh -i ~/.ssh/id_rsa_global user@10.193.0.55
cd /home/erp/vverp

# Распаковать фронтенд
tar -xzf build.tar.gz
sudo docker exec -u root vverp_app rm -rf /var/www/public/build
sudo docker cp build/. vverp_app:/var/www/public/build/
sudo docker exec -u root vverp_app chown -R appuser:appgroup /var/www/public/build
rm -rf build/ build.tar.gz
```

### Шаг 3: Очистить кеш приложения

```bash
cd /home/erp/vverp

# Очистить все кеши
sudo docker exec vverp_app php artisan config:clear
sudo docker exec vverp_app php artisan cache:clear
sudo docker exec vverp_app php artisan route:clear
sudo docker exec vverp_app php artisan view:clear

# Если нужно - запустить миграции (только при первом развертывании)
# sudo docker exec vverp_app php artisan migrate
```

### Шаг 4: Установить ping в контейнер очереди (КРИТИЧНО!)

```bash
# Зайти в контейнер очереди
sudo docker exec -it vverp_queue bash

# Проверить наличие ping
which ping

# Если нет - установить
apt-get update
apt-get install -y iputils-ping

# Проверить что работает
ping -c 2 10.193.67.1

# Если выдает ошибку доступа - дать права
chmod u+s /bin/ping
# ИЛИ
setcap cap_net_raw+ep /bin/ping

# Выйти из контейнера
exit
```

### Шаг 5: Перезапустить контейнер очереди

```bash
# Перезапустить контейнер очереди (ОБЯЗАТЕЛЬНО после обновления кода!)
sudo docker restart vverp_queue

# Подождать 5 секунд
sleep 5

# Проверить что запустился
sudo docker ps | grep vverp_queue

# Посмотреть логи (должны появляться записи о проверках)
sudo docker logs vverp_queue --tail 30 -f
# Ctrl+C чтобы выйти
```

### Шаг 6: Проверить работу

```bash
# 1. Проверить healthcheck (должен работать БЕЗ авторизации)
curl http://10.193.0.55/monitoring/healthcheck

# Ожидаемый результат:
# {
#   "status": "healthy",
#   "checks": {
#     "database": "ok",
#     "last_check_minutes_ago": 2,
#     "active_hosts": 1,
#     "problematic_hosts": 0
#   }
# }

# 2. Запустить ручную проверку
sudo docker exec vverp_app php artisan monitoring:check-hosts --all --sync

# 3. Посмотреть результаты в логах
tail -30 /home/erp/vverp/storage/logs/laravel.log | grep -i "monitoring\|host"
```

### Шаг 7: Настроить scheduler (если еще не настроен)

```bash
# Проверить есть ли cron для Laravel Scheduler
sudo docker exec vverp_app crontab -l

# Если нет - добавить (обычно уже есть в образе)
# * * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1

# Проверить что scheduler работает
sudo docker exec vverp_app php artisan schedule:list | grep monitoring
```

## 🔍 Диагностика проблем

### Проверка 1: Контейнер очереди работает?

```bash
sudo docker ps | grep vverp_queue
sudo docker logs vverp_queue --tail 50
```

### Проверка 2: Команда ping доступна в контейнере очереди?

```bash
sudo docker exec vverp_queue which ping
sudo docker exec vverp_queue ping -c 2 8.8.8.8
```

### Проверка 3: Есть ли сетевая доступность из контейнера?

```bash
# Проверить доступность вашего хоста из контейнера очереди
sudo docker exec vverp_queue ping -c 4 -W 3 10.193.67.1

# Если не работает - проблема в сети/firewall
```

### Проверка 4: Обрабатываются ли задачи очереди?

```bash
# Посмотреть failed jobs
sudo docker exec vverp_app php artisan queue:failed

# Если есть failed jobs - посмотреть детали
sudo docker exec vverp_app php artisan queue:failed

# Очистить failed jobs
sudo docker exec vverp_app php artisan queue:flush
```

### Проверка 5: Логи Laravel

```bash
# В реальном времени
tail -f /home/erp/vverp/storage/logs/laravel.log | grep -i "monitoring\|host\|ping"

# Последние ошибки
tail -100 /home/erp/vverp/storage/logs/laravel.log | grep -i "error\|exception"
```

## 🐛 Типичные проблемы

### "Host unreachable or timeout"

**Причины:**
1. ❌ Команда `ping` не установлена в контейнере `vverp_queue`
2. ❌ Нет сетевого доступа из контейнера к хосту
3. ❌ Firewall блокирует ICMP
4. ❌ Контейнер очереди не перезапущен после обновления кода

**Решение:**
```bash
# 1. Установить ping
sudo docker exec -it vverp_queue bash
apt-get update && apt-get install -y iputils-ping
exit

# 2. Перезапустить контейнер
sudo docker restart vverp_queue

# 3. Тестовая проверка
sudo docker exec vverp_queue ping -c 2 10.193.67.1
```

### "Ping command is not available"

```bash
# Установить в контейнер очереди
sudo docker exec -u root vverp_queue apt-get update
sudo docker exec -u root vverp_queue apt-get install -y iputils-ping
sudo docker restart vverp_queue
```

### "Class HostAvailabilityLog not found"

```bash
# Очистить autoload и кеш
sudo docker exec vverp_app composer dump-autoload
sudo docker exec vverp_app php artisan config:clear
sudo docker exec vverp_app php artisan cache:clear
sudo docker restart vverp_queue
```

### Healthcheck требует авторизацию (редирект на /login)

**Проблема:** Роут healthcheck находится внутри группы с middleware auth.

**Решение:** Обновите routes/web.php - healthcheck должен быть ДО группы `Route::middleware(['auth:sanctum', ...])`:

```php
// ПРАВИЛЬНО - ДО middleware группы
Route::get('/monitoring/healthcheck', [MonitoringController::class, 'healthcheck'])
    ->name('monitoring.healthcheck');

Route::middleware(['auth:sanctum', ...])->group(function () {
    // ... остальные роуты
});
```

## 📊 Мониторинг самой системы мониторинга

### Добавить в внешний мониторинг (Zabbix/Prometheus/etc)

```bash
# HTTP check на healthcheck endpoint
curl -f http://10.193.0.55/monitoring/healthcheck || echo "MONITORING DOWN"

# Проверка статуса контейнера
docker inspect vverp_queue --format='{{.State.Status}}' | grep running || echo "QUEUE DOWN"

# Проверка последних логов на ошибки
tail -100 /home/erp/vverp/storage/logs/laravel.log | grep -c ERROR
```

### Создать cron для самопроверки

```bash
# Добавить в crontab
*/5 * * * * curl -f http://10.193.0.55/monitoring/healthcheck || echo "Monitoring unhealthy" | mail -s "Alert" admin@example.com
```

## 🔄 Обновление в будущем

После любых изменений в коде мониторинга:

```bash
# 1. Скопировать новые файлы на сервер
# 2. Очистить кеш
sudo docker exec vverp_app php artisan config:clear
sudo docker exec vverp_app php artisan cache:clear

# 3. ОБЯЗАТЕЛЬНО перезапустить контейнер очереди!
sudo docker restart vverp_queue

# 4. Проверить логи
sudo docker logs vverp_queue --tail 20 -f
```

## 📝 Полезные команды

```bash
# Статус всех контейнеров
sudo docker ps

# Логи контейнера очереди
sudo docker logs vverp_queue -f

# Логи приложения
sudo docker logs vverp_app -f

# Зайти в контейнер
sudo docker exec -it vverp_queue bash
sudo docker exec -it vverp_app bash

# Перезапустить все контейнеры
sudo docker restart vverp_app vverp_queue vverp_nginx

# Проверить статистику мониторинга
sudo docker exec vverp_app php artisan monitoring:stats --days=7

# Проблемные хосты
sudo docker exec vverp_app php artisan monitoring:problematic

# Очистка старых логов
sudo docker exec vverp_app php artisan monitoring:clean-logs --days=30
```

---

**Важно:** Замена Zabbix на эту систему - это отличное решение для мониторинга внутренних хостов/касс, но не забудьте:
- Настроить уведомления (Telegram/Email) при проблемах
- Регулярно проверять healthcheck endpoint
- Настроить бэкапы базы данных с логами мониторинга
