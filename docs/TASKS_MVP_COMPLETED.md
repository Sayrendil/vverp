# ✅ Tasks Module MVP - Backend Completed!

## 🎉 Что реализовано

### 1. База данных (9 таблиц)
- ✅ `projects` - Проекты
- ✅ `tasks` - Задачи
- ✅ `task_statuses` - Статусы
- ✅ `project_members` - Участники проектов
- ✅ `task_comments` - Комментарии
- ✅ `task_attachments` - Вложения
- ✅ `task_activities` - История изменений
- ✅ `task_labels` - Метки
- ✅ `task_label_assignments` - Связь задач и меток
- ✅ `task_links` - Связи между задачами

### 2. Enums (4 шт.)
- ✅ `TaskType` - типы задач (task, bug, feature, improvement)
- ✅ `TaskPriority` - приоритеты (critical, high, medium, low)
- ✅ `ProjectRole` - роли в проекте (owner, admin, member, viewer)
- ✅ `TaskLinkType` - типы связей (blocks, relates, duplicates, depends_on)

### 3. Models (8 шт.)
- ✅ `Project` - модель проекта
- ✅ `Task` - модель задачи
- ✅ `TaskStatus` - модель статуса
- ✅ `TaskComment` - модель комментария
- ✅ `TaskAttachment` - модель вложения
- ✅ `TaskActivity` - модель активности
- ✅ `TaskLabel` - модель метки
- ✅ `TaskLink` - модель связи

### 4. Policies (2 шт.)
- ✅ `ProjectPolicy` - права доступа к проектам
- ✅ `TaskPolicy` - права доступа к задачам

### 5. Controllers (5 шт.)
- ✅ `ProjectController` - управление проектами
- ✅ `TaskController` - управление задачами
- ✅ `TaskCommentController` - комментарии
- ✅ `TaskAttachmentController` - вложения
- ✅ `ProjectMemberController` - участники проектов

### 6. Routes
- ✅ Все маршруты добавлены в `routes/web.php`

### 7. Seeder
- ✅ `TasksModuleSeeder` - тестовые данные

---

## 🚀 Инструкции по запуску

### 1. Запустить миграции

```bash
# На локальной машине
php artisan migrate

# Или в Docker контейнере
sudo docker exec vverp_app php artisan migrate
```

**Что произойдет:**
- Создадутся 9 новых таблиц в базе данных
- Структура готова к использованию

### 2. Запустить Seeder (опционально, для тестовых данных)

```bash
# На локальной машине
php artisan db:seed --class=TasksModuleSeeder

# Или в Docker контейнере
sudo docker exec vverp_app php artisan db:seed --class=TasksModuleSeeder
```

**Что будет создано:**
- 5 глобальных статусов (Бэклог, К выполнению, В работе, На проверке, Готово)
- 1 тестовый проект "VVERP Development"
- 7 глобальных меток (Срочно, Frontend, Backend, Database, UI/UX, Документация, Тестирование)
- 5 тестовых задач с разными статусами
- 2 подзадачи

### 3. Очистить кэш (если нужно)

```bash
sudo docker exec vverp_app php artisan config:clear
sudo docker exec vverp_app php artisan cache:clear
sudo docker exec vverp_app php artisan route:clear
```

### 4. Проверить routes

```bash
# Посмотреть все маршруты для Tasks
php artisan route:list --name=projects
php artisan route:list --name=tasks
```

---

## 📋 Список маршрутов

### Проекты

| Метод | URL | Название | Действие |
|-------|-----|----------|----------|
| GET | `/projects` | `projects.index` | Список проектов |
| GET | `/projects/create` | `projects.create` | Форма создания проекта |
| POST | `/projects` | `projects.store` | Создать проект |
| GET | `/projects/{key}` | `projects.show` | Kanban доска проекта |
| GET | `/projects/{key}/edit` | `projects.edit` | Форма редактирования |
| PUT | `/projects/{key}` | `projects.update` | Обновить проект |
| DELETE | `/projects/{key}` | `projects.destroy` | Удалить проект |
| GET | `/projects/{key}/settings` | `projects.settings` | Настройки проекта |

### Участники проектов

| Метод | URL | Название | Действие |
|-------|-----|----------|----------|
| POST | `/projects/{key}/members` | `projects.members.store` | Добавить участника |
| PUT | `/projects/{key}/members/{user}` | `projects.members.update-role` | Изменить роль |
| DELETE | `/projects/{key}/members/{user}` | `projects.members.destroy` | Удалить участника |

### Задачи

| Метод | URL | Название | Действие |
|-------|-----|----------|----------|
| GET | `/tasks/{task}` | `tasks.show` | Детали задачи |
| POST | `/tasks` | `tasks.store` | Создать задачу |
| PUT | `/tasks/{task}` | `tasks.update` | Обновить задачу |
| DELETE | `/tasks/{task}` | `tasks.destroy` | Удалить задачу |
| POST | `/tasks/{task}/status` | `tasks.update-status` | Изменить статус |
| POST | `/tasks/{task}/assignee` | `tasks.update-assignee` | Назначить исполнителя |
| POST | `/tasks/{task}/priority` | `tasks.update-priority` | Изменить приоритет |

### Комментарии

| Метод | URL | Название | Действие |
|-------|-----|----------|----------|
| POST | `/tasks/{task}/comments` | `tasks.comments.store` | Добавить комментарий |
| DELETE | `/tasks/comments/{comment}` | `tasks.comments.destroy` | Удалить комментарий |

### Вложения

| Метод | URL | Название | Действие |
|-------|-----|----------|----------|
| POST | `/tasks/{task}/attachments` | `tasks.attachments.store` | Загрузить файл |
| DELETE | `/tasks/attachments/{attachment}` | `tasks.attachments.destroy` | Удалить файл |

---

## 🧪 Тестирование через Tinker

### 1. Проверить создание проекта

```bash
sudo docker exec vverp_app php artisan tinker --execute="
    \$project = \App\Models\Project::first();
    echo 'Project: ' . \$project->name . ' (' . \$project->key . ')' . PHP_EOL;
    echo 'Owner: ' . \$project->owner->name . PHP_EOL;
    echo 'Members: ' . \$project->members->count() . PHP_EOL;
    echo 'Tasks: ' . \$project->tasks->count() . PHP_EOL;
"
```

### 2. Проверить задачи

```bash
sudo docker exec vverp_app php artisan tinker --execute="
    \$task = \App\Models\Task::first();
    echo 'Task: ' . \$task->key . ' - ' . \$task->title . PHP_EOL;
    echo 'Type: ' . \$task->type->label() . ' ' . \$task->type->icon() . PHP_EOL;
    echo 'Priority: ' . \$task->priority->label() . ' ' . \$task->priority->icon() . PHP_EOL;
    echo 'Status: ' . \$task->status->name . PHP_EOL;
    echo 'Reporter: ' . \$task->reporter->name . PHP_EOL;
    echo 'Assignee: ' . (\$task->assignee ? \$task->assignee->name : 'Не назначен') . PHP_EOL;
"
```

### 3. Проверить статусы

```bash
sudo docker exec vverp_app php artisan tinker --execute="
    \$statuses = \App\Models\TaskStatus::whereNull('project_id')->ordered()->get();
    echo 'Global Statuses:' . PHP_EOL;
    foreach (\$statuses as \$status) {
        echo '  ' . \$status->name . ' (' . \$status->slug . ')' . PHP_EOL;
    }
"
```

### 4. Проверить подзадачи

```bash
sudo docker exec vverp_app php artisan tinker --execute="
    \$task = \App\Models\Task::whereNotNull('parent_task_id')->first();
    if (\$task) {
        echo 'Subtask: ' . \$task->key . PHP_EOL;
        echo 'Parent: ' . \$task->parentTask->key . ' - ' . \$task->parentTask->title . PHP_EOL;
    } else {
        echo 'No subtasks found.' . PHP_EOL;
    }
"
```

---

## 📊 Структура базы данных

### Основные связи

```
Project (1) ---- (N) Task
Project (N) ---- (N) User (через project_members)
Task (1) ---- (N) TaskComment
Task (1) ---- (N) TaskAttachment
Task (1) ---- (N) TaskActivity
Task (N) ---- (N) TaskLabel
Task (1) ---- (N) Task (parent_task_id - подзадачи)
Task (N) ---- (N) Task (через task_links)
```

### Ключевые поля

**Project:**
- `key` - уникальный ключ проекта (VVERP, AUTO, INFRA)
- `owner_id` - владелец проекта

**Task:**
- `task_number` - номер в проекте (автоинкремент)
- `key` - виртуальное поле (PROJECT_KEY-TASK_NUMBER)
- `type` - тип задачи (enum)
- `priority` - приоритет (enum)
- `parent_task_id` - родительская задача для подзадач

**TaskStatus:**
- `project_id` - NULL для глобальных статусов
- `is_initial` - начальный статус при создании
- `is_final` - завершающий статус

---

## 🎨 Frontend (следующий этап)

Теперь нужно создать Vue компоненты для:

### 1. Список проектов
**Страница:** `resources/js/Pages/Tasks/Projects/Index.vue`

**Что показывать:**
- Карточки проектов
- Иконка, название, описание
- Количество задач
- Роль пользователя в проекте

### 2. Kanban доска
**Страница:** `resources/js/Pages/Tasks/Projects/Show.vue`

**Компоненты:**
- `KanbanBoard.vue` - контейнер доски
- `KanbanColumn.vue` - колонка статуса
- `TaskCard.vue` - карточка задачи
- Drag & Drop между колонками

### 3. Детальная страница задачи
**Страница:** `resources/js/Pages/Tasks/Tasks/Show.vue`

**Секции:**
- Заголовок и описание
- Метаданные (статус, приоритет, исполнитель, дедлайн)
- Подзадачи
- Комментарии
- Вложения
- История изменений

### 4. Модалки
- Создание/редактирование проекта
- Создание/редактирование задачи

---

## 🔐 Права доступа (Policies)

### ProjectPolicy

| Действие | Кто может |
|----------|-----------|
| `view` | Участники проекта |
| `create` | Только админы системы |
| `update` | Owner, Admin проекта |
| `delete` | Только Owner проекта |
| `manageMembers` | Owner, Admin проекта |
| `createTask` | Owner, Admin, Member |

### TaskPolicy

| Действие | Кто может |
|----------|-----------|
| `view` | Участники проекта |
| `update` | Reporter, Assignee, Owner/Admin проекта |
| `delete` | Reporter, Owner/Admin проекта (не завершенные) |
| `updateStatus` | Member и выше |
| `updateAssignee` | Reporter, Assignee, Owner/Admin |
| `comment` | Все участники проекта |
| `attach` | Member и выше |

---

## 📝 Примеры использования

### Создать проект через API

```php
POST /projects
{
    "name": "VVERP Development",
    "key": "VVERP",
    "description": "Основной проект разработки",
    "icon": "🚀",
    "color": "#3498db"
}
```

### Создать задачу

```php
POST /tasks
{
    "project_id": 1,
    "title": "Исправить баг с уведомлениями",
    "description": "## Проблема\nНе приходят уведомления",
    "type": "bug",
    "priority": "high",
    "assignee_id": 2,
    "due_date": "2025-11-25",
    "story_points": 5
}
```

### Изменить статус (Drag & Drop)

```php
POST /tasks/1/status
{
    "status_id": 3,
    "board_position": 2
}
```

### Добавить комментарий

```php
POST /tasks/1/comments
{
    "content": "Начал работу над задачей"
}
```

---

## ✅ Checklist для продолжения

### Фаза 2: Frontend (Vue компоненты)
- [ ] Список проектов (`Projects/Index.vue`)
- [ ] Kanban доска (`Projects/Show.vue`)
- [ ] Компонент колонки (`KanbanColumn.vue`)
- [ ] Карточка задачи (`TaskCard.vue`)
- [ ] Drag & Drop функционал
- [ ] Детальная страница задачи (`Tasks/Show.vue`)
- [ ] Модалка создания задачи
- [ ] Комментарии компонент
- [ ] История активности

### Фаза 3: Дополнительные фичи
- [ ] Фильтры на Kanban доске
- [ ] Поиск задач
- [ ] Bulk операции
- [ ] Экспорт задач
- [ ] Уведомления (опционально)
- [ ] Telegram интеграция (опционально)

---

## 🎯 Следующие шаги

1. **Протестировать миграции:**
   ```bash
   sudo docker exec vverp_app php artisan migrate
   ```

2. **Запустить Seeder:**
   ```bash
   sudo docker exec vverp_app php artisan db:seed --class=TasksModuleSeeder
   ```

3. **Проверить через Tinker** что данные созданы корректно

4. **Начать разработку Frontend** - создать Vue компоненты

5. **Интегрировать с навигацией** - добавить ссылку "Задачи" в меню

---

## 📚 Связанные документы

- `docs/TASKS_MODULE_DESIGN.md` - Исходное проектирование
- `docs/TASKS_ARCHITECTURE_MVP.md` - Детальная архитектура

---

**Дата:** 2025-11-18
**Версия:** Backend MVP v1.0
**Статус:** ✅ Готово к тестированию

🚀 **Backend полностью готов! Можно переходить к Frontend разработке.**
