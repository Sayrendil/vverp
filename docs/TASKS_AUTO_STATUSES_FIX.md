# 🔧 Исправление автоматического создания статусов для проектов

## Проблема

При создании проекта не создавались дефолтные статусы для Kanban доски, в результате:
- Kanban доска не отображалась
- Статусы проекта: **0** ❌

## Решение

Реализован **Laravel Observer** (`ProjectObserver`), который автоматически создает 5 дефолтных статусов при создании каждого нового проекта.

---

## 📋 Что изменено

### 1. Новый файл: `app/Observers/ProjectObserver.php`

Автоматически создает статусы при создании проекта:
- 🔵 Бэклог
- 📘 К выполнению
- 🟠 В работе
- 🟣 На проверке
- ✅ Завершено

### 2. Обновлен: `app/Providers/AppServiceProvider.php`

```php
Project::observe(ProjectObserver::class);
```

### 3. Обновлен: `database/seeders/TasksModuleSeeder.php`

- ❌ Удален метод `createGlobalStatuses()` (больше не нужен)
- ✅ Статусы создаются автоматически при создании проекта
- ✅ Задачи используют статусы из **конкретного проекта**

---

## 🚀 Инструкции для деплоя

### **Вариант 1: Быстрое исправление (рекомендуется)**

На сервере добавьте статусы в существующий проект вручную:

```bash
ssh -i ~/.ssh/id_rsa_global user@10.193.0.55

sudo docker exec vverp_app php artisan tinker --execute="
    \$project = \App\Models\Project::first();
    \$statuses = [
        ['name' => 'Бэклог', 'slug' => 'backlog', 'color' => '#95a5a6', 'position' => 1, 'is_initial' => true, 'is_final' => false],
        ['name' => 'К выполнению', 'slug' => 'to_do', 'color' => '#3498db', 'position' => 2, 'is_initial' => false, 'is_final' => false],
        ['name' => 'В работе', 'slug' => 'in_progress', 'color' => '#f39c12', 'position' => 3, 'is_initial' => false, 'is_final' => false],
        ['name' => 'На проверке', 'slug' => 'in_review', 'color' => '#9b59b6', 'position' => 4, 'is_initial' => false, 'is_final' => false],
        ['name' => 'Завершено', 'slug' => 'done', 'color' => '#2ecc71', 'position' => 5, 'is_initial' => false, 'is_final' => true],
    ];

    foreach (\$statuses as \$status) {
        \App\Models\TaskStatus::create([
            'project_id' => \$project->id,
            'name' => \$status['name'],
            'slug' => \$status['slug'],
            'color' => \$status['color'],
            'position' => \$status['position'],
            'is_initial' => \$status['is_initial'],
            'is_final' => \$status['is_final'],
        ]);
    }

    echo '✅ Статусы созданы!' . PHP_EOL;
    echo 'Всего статусов: ' . \$project->statuses()->count() . PHP_EOL;
"
```

**Проверка:**

```bash
sudo docker exec vverp_app php artisan tinker --execute="
    \$project = \App\Models\Project::first();
    echo 'Проект: ' . \$project->name . PHP_EOL;
    echo 'Статусов: ' . \$project->statuses()->count() . ' (должно быть 5)' . PHP_EOL;
    echo 'Задач: ' . \$project->tasks()->count() . PHP_EOL;
"
```

---

### **Вариант 2: Полный пересоздание данных**

⚠️ **Внимание:** Удалит все существующие проекты и задачи!

```bash
ssh -i ~/.ssh/id_rsa_global user@10.193.0.55

# 1. Откатить миграции Tasks модуля
sudo docker exec vverp_app php artisan migrate:rollback --step=10

# 2. Запустить миграции заново
sudo docker exec vverp_app php artisan migrate

# 3. Запустить seeder (Observer автоматически создаст статусы)
sudo docker exec vverp_app php artisan db:seed --class=TasksModuleSeeder
```

---

## 🔍 Как работает Observer

Каждый раз при создании проекта (`Project::create()`):

1. Срабатывает событие `created`
2. `ProjectObserver::created()` вызывается автоматически
3. Создаются 5 дефолтных статусов с `project_id = <ID нового проекта>`

**Пример:**

```php
$project = Project::create([
    'name' => 'Новый проект',
    'key' => 'NEW',
    // ...
]);

// ✅ Автоматически создано 5 статусов!
$project->statuses()->count(); // 5
```

---

## ✅ Результат после деплоя

```bash
Projects: 1
Tasks: 7
TaskStatuses: 5

Project: VVERP Development (VVERP)
Statuses in project: 5 ✅ (было 0)
Tasks in project: 7
```

---

## 📦 Деплой через Git

```bash
# 1. Локально закоммитить изменения
git add app/Observers/ProjectObserver.php
git add app/Providers/AppServiceProvider.php
git add database/seeders/TasksModuleSeeder.php
git add docs/TASKS_AUTO_STATUSES_FIX.md
git commit -m "feat: автоматическое создание статусов при создании проекта через Observer"
git push

# 2. На сервере обновить код
ssh -i ~/.ssh/id_rsa_global user@10.193.0.55
cd /home/erp/vverp
git pull

# 3. Добавить статусы (Вариант 1 из инструкций выше)
sudo docker exec vverp_app php artisan tinker --execute="..."

# 4. Очистить кеши
sudo docker exec vverp_app php artisan config:clear
sudo docker exec vverp_app php artisan cache:clear
sudo docker exec vverp_app php artisan route:clear
sudo docker exec vverp_app php artisan view:clear
```

---

## 🎯 Преимущества нового подхода

✅ **Автоматизация** - статусы создаются при каждом новом проекте
✅ **Изоляция** - каждый проект имеет свои статусы
✅ **Гибкость** - можно настроить статусы для конкретного проекта
✅ **Надежность** - невозможно забыть создать статусы

---

## 📝 Для будущих проектов

Теперь при создании нового проекта через веб-интерфейс:

1. Пользователь заполняет форму создания проекта
2. `ProjectController` вызывает `Project::create()`
3. **Observer автоматически создает 5 статусов** ✨
4. Проект сразу готов к использованию с Kanban доской!

Никаких дополнительных действий не требуется! 🎉
