# 🔧 Диагностика проблем мониторинга

## Проблема: Все проверки показывают "Host unreachable or timeout"

### 1. Перезапустить queue worker (обязательно после обновления кода!)

```bash
# На production сервере
cd /home/erp/vverp

# Остановить старые воркеры
sudo supervisorctl stop vverp-monitoring-queue:*

# ИЛИ если supervisor не используется
ps aux | grep "queue:work.*monitoring"
kill -9 <PID_процесса>

# Запустить заново
sudo supervisorctl start vverp-monitoring-queue:*

# ИЛИ вручную
php artisan queue:work --queue=monitoring --sleep=3 --tries=3 --max-time=3600
```

### 2. Проверить доступность команды ping

```bash
# Зайти в контейнер (если приложение в Docker)
sudo docker exec -it vverp_app bash

# Проверить доступность ping
which ping
# Должно вывести: /bin/ping или /usr/bin/ping

# Если команда не найдена - установить
apt-get update && apt-get install -y iputils-ping

# Попробовать вручную пропинговать хост
ping -c 4 -W 3 10.193.67.1
```

### 3. Проверить сетевую доступность из контейнера/сервера

```bash
# Если ping работает, но Laravel не пингует - проблема в правах
# Попробовать от пользователя www-data или appuser
sudo -u www-data ping -c 1 10.193.67.1

# Проверить firewall
iptables -L -n

# Проверить DNS (если используется hostname вместо IP)
nslookup example.com
```

### 4. Проверить логи Laravel

```bash
# Основной лог
tail -f /home/erp/vverp/storage/logs/laravel.log

# Логи queue worker
tail -f /home/erp/vverp/storage/logs/monitoring-queue.log

# Фильтровать только мониторинг
tail -f /home/erp/vverp/storage/logs/laravel.log | grep -i "monitoring\|host.*availability\|ping"
```

### 5. Тестовая проверка в синхронном режиме

```bash
# Запустить проверку синхронно (не через очередь) для отладки
php artisan monitoring:check-hosts --all --sync

# Если появится ошибка - она будет видна сразу
```

### 6. Проверить настройки хоста в БД

```bash
# Зайти в БД
mysql -u erp -p vverp

# Проверить данные хоста
SELECT * FROM hosts WHERE id = 1;

# Проверить последние логи проверок
SELECT * FROM host_availability_logs
WHERE host_id = 1
ORDER BY checked_at DESC
LIMIT 10;
```

### 7. Проверить healthcheck endpoint

```bash
# Должен работать БЕЗ авторизации
curl http://10.193.0.55/monitoring/healthcheck

# Ожидаемый ответ:
{
  "status": "healthy",
  "timestamp": "2025-11-21T12:00:00+00:00",
  "checks": {
    "database": "ok",
    "last_check_minutes_ago": 2,
    "active_hosts": 1,
    "problematic_hosts": 0
  },
  "issues": []
}
```

## Типичные проблемы и решения

### Проблема: "Ping command is not available"
**Решение:**
```bash
# В контейнере
apt-get update && apt-get install -y iputils-ping

# ИЛИ для Alpine Linux
apk add iputils
```

### Проблема: "Permission denied" при выполнении ping
**Решение:**
```bash
# Дать права на ping
chmod u+s /bin/ping
# ИЛИ
setcap cap_net_raw+ep /bin/ping
```

### Проблема: Queue worker не обрабатывает задачи
**Решение:**
```bash
# Проверить статус
sudo supervisorctl status

# Проверить failed jobs
php artisan queue:failed

# Очистить failed jobs и перезапустить
php artisan queue:flush
php artisan queue:restart
```

### Проблема: Хост доступен вручную, но не через Laravel
**Решение:**
1. Проверить timeout в настройках хоста (может быть слишком маленький)
2. Увеличить timeout до 10 секунд
3. Проверить что worker запущен от правильного пользователя
4. Проверить PATH переменную окружения

### Проблема: "Class HostAvailabilityLog not found"
**Решение:**
```bash
# Очистить кеш
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Пересоздать автозагрузку
composer dump-autoload
```

## Пошаговая диагностика

### Шаг 1: Проверка из контейнера
```bash
# Зайти в контейнер
sudo docker exec -it vverp_app bash

# Попробовать ping
ping -c 4 10.193.67.1

# Если не работает - проблема в сети/firewall
# Если работает - идем дальше
```

### Шаг 2: Проверка от имени пользователя приложения
```bash
# В контейнере
su - appuser  # или www-data

# Попробовать ping
ping -c 4 10.193.67.1

# Если не работает - проблема в правах
```

### Шаг 3: Тестовый запуск через artisan
```bash
# Выйти из контейнера, на сервере:
cd /home/erp/vverp

# Запустить синхронную проверку с выводом ошибок
php artisan monitoring:check-hosts --all --sync -vvv

# Смотреть лог в реальном времени
tail -f storage/logs/laravel.log
```

### Шаг 4: Если все еще не работает - проверить код
```bash
# Посмотреть что происходит в Job
cd /home/erp/vverp
cat app/Jobs/CheckHostAvailability.php | grep -A 20 "private function pingHost"

# Убедиться что есть проверка команды ping (строки 130-136)
```

## Быстрая диагностика на production

```bash
#!/bin/bash
echo "=== Monitoring Diagnostics ==="

echo -e "\n1. Queue worker status:"
sudo supervisorctl status | grep monitoring

echo -e "\n2. Last 10 log entries:"
tail -10 /home/erp/vverp/storage/logs/laravel.log | grep -i "host\|monitoring"

echo -e "\n3. Ping availability:"
sudo docker exec vverp_app which ping

echo -e "\n4. Manual ping test:"
sudo docker exec vverp_app ping -c 2 10.193.67.1

echo -e "\n5. Healthcheck:"
curl -s http://10.193.0.55/monitoring/healthcheck | jq

echo -e "\n6. Recent checks from DB:"
mysql -u erp -p -e "SELECT checked_at, is_available, response_time, error_message FROM vverp.host_availability_logs WHERE host_id=1 ORDER BY checked_at DESC LIMIT 5;"

echo -e "\n=== End Diagnostics ==="
```

Сохраните этот скрипт как `diagnose-monitoring.sh` и запустите:
```bash
chmod +x diagnose-monitoring.sh
./diagnose-monitoring.sh
```
