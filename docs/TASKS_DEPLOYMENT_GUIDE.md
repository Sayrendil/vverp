# 🚀 Руководство по развертыванию модуля Tasks

## ✅ Что готово

### Backend (100%)
- ✅ 9 миграций БД
- ✅ 4 Enums
- ✅ 8 Models
- ✅ 2 Policies
- ✅ 5 Controllers
- ✅ Routes
- ✅ Seeder для тестовых данных

### Frontend (100%)
- ✅ Список проектов (`Projects/Index.vue`)
- ✅ Kanban доска (`Projects/Show.vue`)
- ✅ Компоненты (`TaskCard.vue`, `KanbanColumn.vue`)
- ✅ Drag & Drop функционал
- ✅ Детальная страница задачи (`Tasks/Show.vue`)
- ✅ Модалка создания задачи

---

## 📦 Процесс деплоя (Локальная машина)

### Шаг 1: Коммит и push изменений

```bash
cd ~/vkusvill/vverp

# Проверить измененные файлы
git status

# Добавить все изменения
git add .

# Закоммитить с понятным сообщением
git commit -m "feat: Добавлен модуль управления задачами (Tasks)

- Backend: миграции, модели, контроллеры, policies
- Frontend: Kanban доска, карточки задач, drag & drop
- Компоненты: Projects/Index, Projects/Show, Tasks/Show
- Seeder для тестовых данных"

# Отправить на GitHub
git push origin main
```

### Шаг 2: Собрать фронтенд локально

```bash
# Убедиться что находимся в корне проекта
cd ~/vkusvill/vverp

# Установить зависимости (если еще не установлены)
npm install

# Собрать production build
npm run build

# Создать архив для переноса
tar -czf build.tar.gz -C public build/

# Проверить что архив создан
ls -lh build.tar.gz
```

**Ожидаемый результат:**
- Создан файл `build.tar.gz` с фронтендом
- Размер обычно 1-5 MB

---

## 🚀 Процесс деплоя (Сервер)

### Шаг 3: Копировать build на сервер

```bash
# С локальной машины
scp -i ~/.ssh/id_rsa_global build.tar.gz user@10.193.0.55:/home/erp/vverp/

# Проверить что файл скопирован
ssh -i ~/.ssh/id_rsa_global user@10.193.0.55 "ls -lh /home/erp/vverp/build.tar.gz"
```

### Шаг 4: Подключиться к серверу

```bash
ssh -i ~/.ssh/id_rsa_global user@10.193.0.55
```

### Шаг 5: Обновить backend

```bash
# Перейти в директорию проекта
cd /home/erp/vverp

# Получить изменения с GitHub
git pull origin main

# Проверить что изменения подтянулись
git log -1
```

### Шаг 6: Запустить миграции

```bash
# Запустить миграции БД (создаст 9 новых таблиц)
sudo docker exec vverp_app php artisan migrate

# Если хотите с тестовыми данными - запустить seeder
sudo docker exec vverp_app php artisan db:seed --class=TasksModuleSeeder
```

**Проверка миграций:**
```bash
# Проверить что таблицы созданы
sudo docker exec vverp_app php artisan tinker --execute="
    echo 'Projects: ' . \App\Models\Project::count() . PHP_EOL;
    echo 'Tasks: ' . \App\Models\Task::count() . PHP_EOL;
    echo 'Task Statuses: ' . \App\Models\TaskStatus::whereNull('project_id')->count() . PHP_EOL;
"
```

### Шаг 7: Развернуть фронтенд

```bash
# Распаковать архив
cd /home/erp/vverp
tar -xzf build.tar.gz

# Удалить старый build в контейнере
sudo docker exec -u root vverp_app rm -rf /var/www/public/build

# Скопировать новый build в контейнер
sudo docker cp build/. vverp_app:/var/www/public/build/

# Установить правильные права
sudo docker exec -u root vverp_app chown -R appuser:appgroup /var/www/public/build

# Очистить временные файлы
rm -rf build/ build.tar.gz
```

### Шаг 8: Очистить кэши Laravel

```bash
# Очистить все кэши
sudo docker exec vverp_app php artisan config:clear
sudo docker exec vverp_app php artisan cache:clear
sudo docker exec vverp_app php artisan route:clear
sudo docker exec vverp_app php artisan view:clear

# Опционально: оптимизировать для production
sudo docker exec vverp_app php artisan config:cache
sudo docker exec vverp_app php artisan route:cache
```

### Шаг 9: Проверить routes

```bash
# Проверить что новые routes зарегистрированы
sudo docker exec vverp_app php artisan route:list | grep projects
sudo docker exec vverp_app php artisan route:list | grep tasks
```

**Ожидаемый результат:**
```
GET|HEAD  projects ............. projects.index
POST      projects ............. projects.store
GET|HEAD  projects/create ...... projects.create
GET|HEAD  projects/{project:key} projects.show
...
GET|HEAD  tasks/{task} ......... tasks.show
POST      tasks ................ tasks.store
...
```

### Шаг 10: Проверить работу приложения

```bash
# Проверить логи на ошибки
sudo docker logs vverp_app --tail 50

# Проверить что nginx работает
sudo docker exec vverp_nginx nginx -t
```

---

## 🧪 Тестирование после деплоя

### 1. Проверить доступ к модулю

Откройте в браузере:
- `http://10.193.0.55/projects` - должен открыться список проектов

### 2. Проверить через Tinker

```bash
sudo docker exec vverp_app php artisan tinker --execute="
    \$user = \App\Models\User::first();
    echo 'User: ' . \$user->name . PHP_EOL;
    echo 'Is Admin: ' . (\$user->isAdmin() ? 'Yes' : 'No') . PHP_EOL;
    echo PHP_EOL;

    \$projects = \App\Models\Project::all();
    echo 'Projects count: ' . \$projects->count() . PHP_EOL;
    if (\$projects->count() > 0) {
        \$project = \$projects->first();
        echo 'First project: ' . \$project->name . ' (' . \$project->key . ')' . PHP_EOL;
        echo 'Tasks in project: ' . \$project->tasks()->count() . PHP_EOL;
    }
"
```

### 3. Проверить права доступа

```bash
sudo docker exec vverp_app php artisan tinker --execute="
    \$user = \App\Models\User::where('role', 'admin')->first();
    \$project = \App\Models\Project::first();

    if (\$user && \$project) {
        echo 'User: ' . \$user->name . PHP_EOL;
        echo 'Project: ' . \$project->name . PHP_EOL;
        echo 'Can view: ' . (\$user->can('view', \$project) ? 'Yes' : 'No') . PHP_EOL;
        echo 'Can create: ' . (\$user->can('create', \App\Models\Project::class) ? 'Yes' : 'No') . PHP_EOL;
    }
"
```

---

## 📋 Полный checklist

### Подготовка (Локально)
- [ ] Проверить что все файлы закоммичены
- [ ] Push на GitHub
- [ ] Собрать фронтенд (`npm run build`)
- [ ] Создать архив (`tar -czf build.tar.gz`)
- [ ] Скопировать архив на сервер (`scp`)

### Деплой (Сервер)
- [ ] Подключиться к серверу
- [ ] `git pull` для получения изменений
- [ ] Запустить миграции
- [ ] (Опционально) Запустить seeder
- [ ] Распаковать и скопировать build
- [ ] Установить права на файлы
- [ ] Очистить кэши Laravel
- [ ] Проверить routes
- [ ] Проверить логи

### Тестирование
- [ ] Открыть `/projects` в браузере
- [ ] Проверить что страница загружается
- [ ] (Если есть Seeder) Увидеть тестовый проект
- [ ] Создать новый проект (если админ)
- [ ] Открыть Kanban доску
- [ ] Создать тестовую задачу
- [ ] Проверить Drag & Drop
- [ ] Открыть детальную страницу задачи

---

## 🔥 Быстрый деплой (одна команда)

### Локально:
```bash
cd ~/vkusvill/vverp && \
npm run build && \
tar -czf build.tar.gz -C public build/ && \
scp -i ~/.ssh/id_rsa_global build.tar.gz user@10.193.0.55:/home/erp/vverp/
```

### На сервере:
```bash
cd /home/erp/vverp && \
git pull && \
sudo docker exec vverp_app php artisan migrate && \
tar -xzf build.tar.gz && \
sudo docker exec -u root vverp_app rm -rf /var/www/public/build && \
sudo docker cp build/. vverp_app:/var/www/public/build/ && \
sudo docker exec -u root vverp_app chown -R appuser:appgroup /var/www/public/build && \
rm -rf build/ build.tar.gz && \
sudo docker exec vverp_app php artisan config:clear && \
sudo docker exec vverp_app php artisan cache:clear && \
sudo docker exec vverp_app php artisan route:clear && \
sudo docker exec vverp_app php artisan view:clear && \
echo "✅ Деплой завершен!"
```

---

## 🐛 Возможные проблемы и решения

### Проблема 1: Миграции не применяются

**Ошибка:**
```
SQLSTATE[HY000]: General error: 1 table "projects" already exists
```

**Решение:**
```bash
# Откатить последнюю миграцию
sudo docker exec vverp_app php artisan migrate:rollback --step=1

# Или запустить только новые миграции
sudo docker exec vverp_app php artisan migrate --force
```

### Проблема 2: Routes не найдены (404)

**Решение:**
```bash
# Очистить кэш routes
sudo docker exec vverp_app php artisan route:clear

# Проверить что routes зарегистрированы
sudo docker exec vverp_app php artisan route:list | grep projects
```

### Проблема 3: Frontend не обновляется

**Решение:**
```bash
# Полностью удалить старый build
sudo docker exec -u root vverp_app rm -rf /var/www/public/build

# Скопировать заново
sudo docker cp build/. vverp_app:/var/www/public/build/

# Проверить права
sudo docker exec -u root vverp_app ls -la /var/www/public/build/

# Очистить browser cache (Ctrl+Shift+R)
```

### Проблема 4: 403 Forbidden при доступе к проектам

**Причина:** Пользователь не является участником ни одного проекта

**Решение:**
```bash
# Добавить пользователя в тестовый проект
sudo docker exec vverp_app php artisan tinker --execute="
    \$user = \App\Models\User::first();
    \$project = \App\Models\Project::first();

    if (\$user && \$project) {
        \$project->members()->attach(\$user->id, ['role' => 'member']);
        echo 'User ' . \$user->name . ' added to project ' . \$project->name . PHP_EOL;
    }
"
```

### Проблема 5: Drag & Drop не работает

**Причина:** JavaScript не загружается

**Решение:**
1. Проверить консоль браузера (F12)
2. Убедиться что `manifest.json` существует:
```bash
sudo docker exec vverp_app ls -la /var/www/public/build/manifest.json
```
3. Пересобрать фронтенд

---

## 📊 Структура после деплоя

### База данных (9 новых таблиц):
```
projects
tasks
task_statuses
project_members
task_comments
task_attachments
task_activities
task_labels
task_label_assignments
task_links
```

### Routes (40+ новых):
```
/projects          - Список проектов
/projects/{key}    - Kanban доска
/tasks/{id}        - Детальная страница задачи
+ API endpoints для действий
```

### Frontend файлы:
```
resources/js/Pages/Tasks/
  ├── Projects/
  │   ├── Index.vue      (список проектов)
  │   └── Show.vue       (Kanban доска)
  └── Tasks/
      └── Show.vue       (детальная страница)

resources/js/Components/Tasks/
  ├── TaskCard.vue       (карточка задачи)
  └── KanbanColumn.vue   (колонка Kanban)
```

---

## 🎉 После успешного деплоя

1. **Добавить ссылку в навигацию** (опционально)
2. **Создать первые проекты** для команды
3. **Настроить роли участников**
4. **Обучить пользователей** работе с системой

---

## 📚 Дополнительные команды

### Экспорт структуры БД

```bash
sudo docker exec vverp_db mysqldump -u root -p[PASSWORD] [DB_NAME] --no-data > schema.sql
```

### Backup проектов и задач

```bash
sudo docker exec vverp_db mysqldump -u root -p[PASSWORD] [DB_NAME] projects tasks task_statuses project_members > tasks_backup.sql
```

### Просмотр логов в реальном времени

```bash
# Логи приложения
sudo docker exec vverp_app tail -f storage/logs/laravel.log

# Логи nginx
sudo docker logs -f vverp_nginx

# Логи queue worker (Telegram бот)
sudo docker logs -f vverp_queue
```

---

**Дата:** 2025-11-18
**Версия:** 1.0
**Автор:** AI Assistant

🚀 **Удачного деплоя!**
