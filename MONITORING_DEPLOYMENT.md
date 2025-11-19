# 🚀 Развертывание модуля мониторинга хостов

## ✅ Созданные компоненты

### Backend (Laravel)

#### Миграции базы данных
- ✅ `database/migrations/2025_11_19_175655_create_hosts_table.php`
- ✅ `database/migrations/2025_11_19_175722_create_host_availability_logs_table.php`

#### Модели
- ✅ `app/Models/Host.php` - Модель хоста (реализует Dictionary)
- ✅ `app/Models/HostAvailabilityLog.php` - Модель лога проверки
- ✅ `app/Models/Store.php` - Добавлена связь с хостами

#### Jobs (Очереди)
- ✅ `app/Jobs/CheckHostAvailability.php` - Job для проверки доступности через ping

#### Сервисы
- ✅ `app/Services/MonitoringService.php` - Централизованная бизнес-логика мониторинга
- ✅ `app/Services/DictionaryService.php` - Добавлен справочник hosts

#### Контроллеры
- ✅ `app/Http/Controllers/MonitoringController.php` - Контроллер для веб-интерфейса

#### Команды (Artisan)
- ✅ `app/Console/Commands/MonitoringCheckHosts.php` - Проверка хостов
- ✅ `app/Console/Commands/MonitoringStatistics.php` - Просмотр статистики
- ✅ `app/Console/Commands/MonitoringProblematicHosts.php` - Поиск проблемных хостов
- ✅ `app/Console/Commands/MonitoringCleanLogs.php` - Очистка старых логов

#### Маршруты
- ✅ `routes/web.php` - Добавлены маршруты для мониторинга
- ✅ `routes/console.php` - Настроен Scheduler

### Frontend (Vue.js)

#### Компоненты
- ✅ `resources/js/Pages/Monitoring/Dashboard.vue` - Дашборд мониторинга
- ✅ `resources/js/Pages/Monitoring/HostDetails.vue` - Детали хоста
- ✅ `resources/js/Components/Layout/NavigationLinks.vue` - Добавлена ссылка в меню

### Документация
- ✅ `docs/MONITORING_MODULE.md` - Полная документация модуля
- ✅ `docs/MONITORING_QUICK_START.md` - Быстрый старт
- ✅ `MONITORING_DEPLOYMENT.md` - Этот файл

---

## 📋 Шаги развертывания

### 1. Запустить миграции

```bash
cd ~/vkusvill/vverp
php artisan migrate
```

Ожидаемый результат:
```
Running migrations.
2025_11_19_175655_create_hosts_table .................... DONE
2025_11_19_175722_create_host_availability_logs_table ... DONE
```

### 2. Собрать фронтенд

```bash
npm run build
```

Или для development:
```bash
npm run dev
```

### 3. Добавить тестовые хосты

Через веб-интерфейс:
1. Войдите как администратор
2. Перейдите в **Справочники → Хосты для мониторинга**
3. Добавьте несколько хостов для тестирования

Или через tinker:
```bash
php artisan tinker
```

```php
use App\Models\Host;
use App\Models\Store;

$store = Store::first();

Host::create([
    'store_id' => $store->id,
    'name' => 'Локальный хост',
    'ip_address' => '127.0.0.1',
    'description' => 'Тестовый хост для проверки',
    'is_active' => true,
    'check_interval' => 5,
    'timeout' => 3,
]);

Host::create([
    'store_id' => $store->id,
    'name' => 'Google DNS',
    'ip_address' => '8.8.8.8',
    'description' => 'Тест внешней сети',
    'is_active' => true,
    'check_interval' => 5,
    'timeout' => 3,
]);
```

### 4. Тестовая проверка (синхронная)

```bash
# Проверить все хосты синхронно (для тестирования)
php artisan monitoring:check-hosts --all --sync
```

Ожидаемый вывод:
```
🔍 Запуск проверки доступности хостов...
Проверка всех активных хостов
✅ Запущено проверок: 2
✅ Проверки выполнены синхронно
```

### 5. Просмотреть статистику

```bash
# Общая статистика
php artisan monitoring:stats

# Статистика по конкретному хосту
php artisan monitoring:stats --host=1
```

### 6. Настроить Queue Worker (Production)

Создайте файл Supervisor конфигурации:

```bash
sudo nano /etc/supervisor/conf.d/vverp-monitoring-queue.conf
```

Вставьте:
```ini
[program:vverp-monitoring-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /home/erp/vverp/artisan queue:work --queue=monitoring --sleep=3 --tries=2 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=erp
numprocs=2
redirect_stderr=true
stdout_logfile=/home/erp/vverp/storage/logs/monitoring-queue.log
stopwaitsecs=3600
```

Перезагрузите Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start vverp-monitoring-queue:*
```

### 7. Проверить работу Queue Worker

```bash
# Запустить проверку через очередь
php artisan monitoring:check-hosts --all

# Проверить статус очереди
php artisan queue:monitor monitoring

# Проверить Supervisor
sudo supervisorctl status vverp-monitoring-queue:*
```

### 8. Проверить веб-интерфейс

1. Откройте браузер
2. Войдите как администратор
3. Перейдите в **Мониторинг** (в боковом меню)
4. Вы должны увидеть дашборд с статистикой

### 9. Проверить Scheduler

```bash
# Посмотреть список запланированных задач
php artisan schedule:list
```

Вы должны увидеть:
- `monitoring:check-hosts` - каждые 5 минут
- `monitoring:clean-logs --days=30` - ежедневно в 03:00

Убедитесь, что cron настроен:
```bash
crontab -l
```

Должна быть строка:
```
* * * * * cd /home/erp/vverp && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧪 Тестирование

### Тест 1: Синхронная проверка

```bash
php artisan monitoring:check-hosts --all --sync
php artisan monitoring:stats
```

### Тест 2: Асинхронная проверка через очередь

Терминал 1 (Queue Worker):
```bash
php artisan queue:work --queue=monitoring
```

Терминал 2 (Запуск проверок):
```bash
php artisan monitoring:check-hosts --all
```

### Тест 3: Проверка конкретного магазина

```bash
php artisan monitoring:check-hosts --store=1 --sync
```

### Тест 4: Поиск проблемных хостов

```bash
# Создайте хост с неверным IP
php artisan tinker
```

```php
Host::create([
    'store_id' => 1,
    'name' => 'Несуществующий хост',
    'ip_address' => '192.168.255.254',
    'is_active' => true,
    'check_interval' => 5,
    'timeout' => 1,
]);
```

```bash
# Проверьте его несколько раз
php artisan monitoring:check-hosts --all --sync
php artisan monitoring:check-hosts --all --sync
php artisan monitoring:check-hosts --all --sync

# Найдите проблемные хосты
php artisan monitoring:problematic
```

### Тест 5: Веб-интерфейс

1. Откройте `/monitoring` в браузере
2. Проверьте отображение статистики
3. Нажмите "Проверить все" - должно добавить задачи в очередь
4. Кликните на хост - откроется детальная информация
5. Нажмите "Проверить сейчас" - должно запустить проверку

---

## 📊 Структура базы данных

### Таблица `hosts`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint | ID хоста |
| store_id | bigint | ID магазина |
| name | varchar | Название хоста |
| ip_address | varchar | IP адрес |
| description | text | Описание |
| is_active | boolean | Активен ли |
| check_interval | int | Интервал проверки (мин) |
| timeout | int | Таймаут (сек) |
| created_at | timestamp | Создано |
| updated_at | timestamp | Обновлено |

### Таблица `host_availability_logs`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint | ID лога |
| host_id | bigint | ID хоста |
| is_available | boolean | Доступен ли |
| response_time | int | Время отклика (мс) |
| packet_loss | int | Потеря пакетов (%) |
| error_message | text | Сообщение об ошибке |
| checked_at | timestamp | Время проверки |
| created_at | timestamp | Создано |
| updated_at | timestamp | Обновлено |

---

## 🔧 Конфигурация

### Очередь мониторинга

Очередь: `monitoring`

**Параметры Job:**
- Попытки: 2
- Таймаут: 30 секунд
- Backoff: 5, 15 секунд

### Scheduler

**Проверка хостов:**
- Частота: каждые 5 минут
- Команда: `monitoring:check-hosts`
- Очередь: да
- Без перекрытия: да

**Очистка логов:**
- Частота: ежедневно в 03:00
- Команда: `monitoring:clean-logs --days=30`
- Удаление: логи старше 30 дней

### Supervisor

**Процессы:** 2 worker'а
**Автозапуск:** да
**Автоперезапуск:** да
**Логи:** `storage/logs/monitoring-queue.log`

---

## 📝 Доступные команды

| Команда | Описание |
|---------|----------|
| `php artisan monitoring:check-hosts` | Проверить хосты по расписанию |
| `php artisan monitoring:check-hosts --all` | Проверить все активные хосты |
| `php artisan monitoring:check-hosts --store=ID` | Проверить хосты магазина |
| `php artisan monitoring:check-hosts --sync` | Синхронная проверка |
| `php artisan monitoring:stats` | Общая статистика |
| `php artisan monitoring:stats --host=ID` | Статистика хоста |
| `php artisan monitoring:stats --days=N` | Статистика за N дней |
| `php artisan monitoring:problematic` | Проблемные хосты |
| `php artisan monitoring:clean-logs` | Очистить старые логи |

---

## 🚨 Решение проблем

### Проблема: Миграции не запускаются

**Решение:**
```bash
php artisan migrate:status
php artisan migrate --force
```

### Проблема: Queue Worker не работает

**Решение:**
```bash
# Проверить статус
sudo supervisorctl status vverp-monitoring-queue:*

# Перезапустить
sudo supervisorctl restart vverp-monitoring-queue:*

# Посмотреть логи
tail -f storage/logs/monitoring-queue.log
```

### Проблема: Все хосты показывают "недоступен"

**Решение:**
1. Проверьте ping вручную: `ping 127.0.0.1`
2. Проверьте права: `ls -la $(which ping)`
3. Проверьте сеть: `ip addr`
4. Увеличьте timeout в настройках хоста

### Проблема: Scheduler не запускается

**Решение:**
```bash
# Проверить cron
crontab -l

# Запустить вручную
php artisan schedule:run

# Проверить логи
tail -f storage/logs/laravel.log
```

---

## ✅ Чеклист развертывания

- [ ] Миграции выполнены
- [ ] Фронтенд собран
- [ ] Тестовые хосты добавлены
- [ ] Синхронная проверка работает
- [ ] Queue Worker настроен
- [ ] Supervisor настроен и запущен
- [ ] Scheduler проверен
- [ ] Cron настроен
- [ ] Веб-интерфейс работает
- [ ] Дашборд отображает данные
- [ ] Детали хоста работают
- [ ] Кнопка "Проверить все" работает
- [ ] Документация прочитана

---

## 📚 Документация

- [MONITORING_MODULE.md](docs/MONITORING_MODULE.md) - Полная документация
- [MONITORING_QUICK_START.md](docs/MONITORING_QUICK_START.md) - Быстрый старт
- [QUEUE_SETUP.md](docs/QUEUE_SETUP.md) - Настройка очередей
- [SUPERVISOR.md](docs/SUPERVISOR.md) - Настройка Supervisor

---

**Готово!** 🎉 Модуль мониторинга развернут и готов к работе!

Следующие шаги:
1. Добавьте все хосты для мониторинга
2. Настройте уведомления (будущая функция)
3. Создайте дашборд виджеты
4. Экспортируйте отчеты

**Дата:** 2025-11-19
**Версия:** 1.0.0
