# 🏗️ Техническая архитектура модуля "Задачи" (Tasks) - MVP

## 🎯 Цель модуля

Система управления задачами для:
- ✅ Доработок и улучшений системы
- ✅ Автоматизации процессов
- ✅ Планирования будущих задач
- ✅ Отслеживания прогресса работ

**Методология:** Kanban (непрерывный поток задач)

---

## 📊 Структура данных

### 1. Проекты (Projects)

**Назначение:** Группировка задач по направлениям

```sql
CREATE TABLE projects (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,                -- Название проекта
    key VARCHAR(10) NOT NULL UNIQUE,           -- Ключ проекта (VVERP, AUTO, TICKET)
    description TEXT,                          -- Описание
    icon VARCHAR(50) DEFAULT '📁',             -- Иконка (emoji)
    color VARCHAR(7) DEFAULT '#3498db',        -- Цвет проекта
    owner_id BIGINT,                           -- Владелец проекта
    is_active BOOLEAN DEFAULT TRUE,            -- Активен ли проект

    -- Настройки
    default_assignee_id BIGINT,                -- Исполнитель по умолчанию

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (owner_id) REFERENCES users(id),
    FOREIGN KEY (default_assignee_id) REFERENCES users(id)
);

-- Индексы
CREATE INDEX idx_projects_active ON projects(is_active);
CREATE INDEX idx_projects_key ON projects(key);
```

**Примеры проектов:**
```
VVERP  - "Основная система" (все задачи по системе vverp)
AUTO   - "Автоматизации" (скрипты, боты, интеграции)
INFRA  - "Инфраструктура" (сервера, docker, деплой)
```

### 2. Задачи (Tasks)

**Назначение:** Единица работы

```sql
CREATE TABLE tasks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT NOT NULL,                -- Проект
    task_number INT NOT NULL,                  -- Номер в проекте (1, 2, 3...)

    -- Основная информация
    title VARCHAR(500) NOT NULL,               -- Заголовок
    description TEXT,                          -- Описание (Markdown)

    -- Классификация
    type ENUM('task', 'bug', 'feature', 'improvement') DEFAULT 'task',
    priority ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
    status_id BIGINT NOT NULL,                 -- Статус (связь с таблицей task_statuses)

    -- Участники
    reporter_id BIGINT NOT NULL,               -- Кто создал
    assignee_id BIGINT,                        -- Кто исполняет

    -- Иерархия
    parent_task_id BIGINT,                     -- Родительская задача (для подзадач)

    -- Временные метки
    due_date DATE,                             -- Дедлайн
    started_at TIMESTAMP,                      -- Когда взята в работу
    completed_at TIMESTAMP,                    -- Когда завершена

    -- Оценки
    story_points TINYINT,                      -- Story Points (1-8)
    estimated_hours DECIMAL(8,2),              -- Оценка в часах

    -- Позиция на доске (для drag&drop)
    board_position INT DEFAULT 0,              -- Позиция в колонке

    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,                      -- Soft delete

    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (reporter_id) REFERENCES users(id),
    FOREIGN KEY (assignee_id) REFERENCES users(id),
    FOREIGN KEY (parent_task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (status_id) REFERENCES task_statuses(id),

    UNIQUE KEY unique_task_number (project_id, task_number)
);

-- Индексы для производительности
CREATE INDEX idx_tasks_project ON tasks(project_id);
CREATE INDEX idx_tasks_assignee ON tasks(assignee_id);
CREATE INDEX idx_tasks_status ON tasks(status_id);
CREATE INDEX idx_tasks_parent ON tasks(parent_task_id);
CREATE INDEX idx_tasks_due_date ON tasks(due_date);
CREATE INDEX idx_tasks_board_position ON tasks(status_id, board_position);
```

**Ключ задачи формируется как:** `{PROJECT_KEY}-{TASK_NUMBER}`
- Примеры: `VVERP-1`, `AUTO-15`, `INFRA-3`

### 3. Статусы задач (Task Statuses)

**Назначение:** Состояния задачи на Kanban доске

```sql
CREATE TABLE task_statuses (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT,                         -- NULL = глобальный, иначе для проекта
    name VARCHAR(100) NOT NULL,                -- Название статуса
    slug VARCHAR(100) NOT NULL,                -- Системное имя (to_do, in_progress)
    color VARCHAR(7) DEFAULT '#95a5a6',        -- Цвет колонки
    position INT DEFAULT 0,                    -- Порядок на доске
    is_initial BOOLEAN DEFAULT FALSE,          -- Начальный статус (при создании)
    is_final BOOLEAN DEFAULT FALSE,            -- Финальный статус (завершено)

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Индексы
CREATE INDEX idx_task_statuses_project ON task_statuses(project_id);
CREATE INDEX idx_task_statuses_position ON task_statuses(project_id, position);
```

**Дефолтные статусы для Kanban:**
```sql
INSERT INTO task_statuses (name, slug, color, position, is_initial, is_final) VALUES
('Бэклог', 'backlog', '#95a5a6', 1, TRUE, FALSE),      -- Серый
('К выполнению', 'to_do', '#3498db', 2, FALSE, FALSE), -- Синий
('В работе', 'in_progress', '#f39c12', 3, FALSE, FALSE), -- Оранжевый
('На проверке', 'in_review', '#9b59b6', 4, FALSE, FALSE), -- Фиолетовый
('Готово', 'done', '#27ae60', 5, FALSE, TRUE);          -- Зеленый
```

### 4. Участники проектов (Project Members)

**Назначение:** Кто имеет доступ к проекту

```sql
CREATE TABLE project_members (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    role ENUM('owner', 'admin', 'member', 'viewer') DEFAULT 'member',

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    UNIQUE KEY unique_project_member (project_id, user_id)
);

-- Индексы
CREATE INDEX idx_project_members_project ON project_members(project_id);
CREATE INDEX idx_project_members_user ON project_members(user_id);
```

**Роли:**
- **Owner** - владелец (все права + удаление проекта)
- **Admin** - администратор (управление настройками и участниками)
- **Member** - участник (создание и редактирование задач)
- **Viewer** - наблюдатель (только просмотр)

### 5. Комментарии к задачам (Task Comments)

```sql
CREATE TABLE task_comments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    task_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    content TEXT NOT NULL,                     -- Текст комментария (Markdown)

    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,                      -- Soft delete

    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Индексы
CREATE INDEX idx_task_comments_task ON task_comments(task_id);
CREATE INDEX idx_task_comments_user ON task_comments(user_id);
```

### 6. Вложения к задачам (Task Attachments)

```sql
CREATE TABLE task_attachments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    task_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,                   -- Кто загрузил

    file_name VARCHAR(255) NOT NULL,           -- Исходное имя файла
    file_path VARCHAR(500) NOT NULL,           -- Путь в storage
    file_type VARCHAR(50),                     -- MIME type
    file_size INT,                             -- Размер в байтах

    created_at TIMESTAMP,

    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Индексы
CREATE INDEX idx_task_attachments_task ON task_attachments(task_id);
```

### 7. История изменений (Task Activity Log)

**Назначение:** Отслеживание всех изменений задачи

```sql
CREATE TABLE task_activities (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    task_id BIGINT NOT NULL,
    user_id BIGINT,                            -- Кто сделал изменение

    action VARCHAR(50) NOT NULL,               -- created, updated, commented, etc.
    field VARCHAR(100),                        -- Какое поле изменено (status, assignee)
    old_value TEXT,                            -- Старое значение (JSON)
    new_value TEXT,                            -- Новое значение (JSON)

    created_at TIMESTAMP,

    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Индексы
CREATE INDEX idx_task_activities_task ON task_activities(task_id);
CREATE INDEX idx_task_activities_created ON task_activities(created_at);
```

### 8. Метки (Labels/Tags)

```sql
CREATE TABLE task_labels (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT,                         -- NULL = глобальная метка
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#95a5a6',

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE task_label_assignments (
    task_id BIGINT NOT NULL,
    label_id BIGINT NOT NULL,

    created_at TIMESTAMP,

    PRIMARY KEY (task_id, label_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (label_id) REFERENCES task_labels(id) ON DELETE CASCADE
);
```

### 9. Связи между задачами (Task Links)

```sql
CREATE TABLE task_links (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    source_task_id BIGINT NOT NULL,            -- Исходная задача
    target_task_id BIGINT NOT NULL,            -- Целевая задача
    link_type ENUM('blocks', 'relates', 'duplicates', 'depends_on') DEFAULT 'relates',

    created_at TIMESTAMP,

    FOREIGN KEY (source_task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (target_task_id) REFERENCES tasks(id) ON DELETE CASCADE,

    UNIQUE KEY unique_task_link (source_task_id, target_task_id, link_type)
);
```

**Типы связей:**
- **blocks** - блокирует (A блокирует B)
- **relates** - связана с (общая связь)
- **duplicates** - дублирует (дубликат)
- **depends_on** - зависит от (A зависит от B)

---

## 🎨 Фронтенд (Vue.js/Inertia)

### Структура страниц

```
resources/js/Pages/Tasks/
├── Projects/
│   ├── Index.vue          # Список проектов
│   ├── Show.vue           # Доска проекта (Kanban)
│   ├── Create.vue         # Создание проекта
│   ├── Edit.vue           # Редактирование проекта
│   └── Settings.vue       # Настройки проекта (статусы, участники)
│
├── Tasks/
│   ├── Show.vue           # Детальная страница задачи
│   ├── Create.vue         # Создание задачи (модалка)
│   └── Edit.vue           # Редактирование задачи (модалка)
│
└── Components/
    ├── KanbanBoard.vue    # Kanban доска
    ├── KanbanColumn.vue   # Колонка доски
    ├── TaskCard.vue       # Карточка задачи
    ├── TaskModal.vue      # Модалка с задачей
    ├── CommentsList.vue   # Список комментариев
    ├── ActivityLog.vue    # История изменений
    ├── TaskFilters.vue    # Фильтры задач
    └── TaskPriority.vue   # Компонент приоритета
```

### Kanban доска (главный экран)

**URL:** `/projects/{key}/board` (например `/projects/VVERP/board`)

**Функционал:**
```vue
<template>
  <div class="kanban-board">
    <!-- Заголовок проекта -->
    <div class="board-header">
      <h1>{{ project.icon }} {{ project.name }}</h1>
      <button @click="openCreateTaskModal">+ Создать задачу</button>
    </div>

    <!-- Фильтры -->
    <TaskFilters
      v-model:assignee="filters.assignee"
      v-model:priority="filters.priority"
      v-model:labels="filters.labels"
      v-model:search="filters.search"
    />

    <!-- Kanban колонки -->
    <div class="kanban-columns">
      <KanbanColumn
        v-for="status in statuses"
        :key="status.id"
        :status="status"
        :tasks="getTasksByStatus(status.id)"
        @task-moved="handleTaskMoved"
        @task-clicked="openTaskModal"
      />
    </div>
  </div>
</template>
```

**Особенности:**
- ✅ Drag & Drop между колонками
- ✅ Фильтрация в реальном времени
- ✅ Быстрое создание задач
- ✅ Клик на карточку → модалка с деталями

### Карточка задачи

```vue
<template>
  <div
    class="task-card"
    :class="[`priority-${task.priority}`, { 'has-subtasks': task.subtasks_count > 0 }]"
    draggable="true"
    @click="$emit('clicked', task)"
  >
    <!-- Ключ и тип -->
    <div class="task-header">
      <span class="task-key">{{ task.key }}</span>
      <TaskTypeIcon :type="task.type" />
      <TaskPriorityIcon :priority="task.priority" />
    </div>

    <!-- Заголовок -->
    <h3 class="task-title">{{ task.title }}</h3>

    <!-- Метки -->
    <div v-if="task.labels?.length" class="task-labels">
      <span
        v-for="label in task.labels"
        :key="label.id"
        class="label"
        :style="{ backgroundColor: label.color }"
      >
        {{ label.name }}
      </span>
    </div>

    <!-- Футер -->
    <div class="task-footer">
      <!-- Подзадачи -->
      <div v-if="task.subtasks_count" class="subtasks">
        ✓ {{ task.completed_subtasks_count }}/{{ task.subtasks_count }}
      </div>

      <!-- Вложения -->
      <div v-if="task.attachments_count" class="attachments">
        📎 {{ task.attachments_count }}
      </div>

      <!-- Комментарии -->
      <div v-if="task.comments_count" class="comments">
        💬 {{ task.comments_count }}
      </div>

      <!-- Исполнитель -->
      <UserAvatar
        v-if="task.assignee"
        :user="task.assignee"
        size="small"
      />
    </div>
  </div>
</template>
```

### Модалка детальной задачи

```vue
<template>
  <Modal :show="show" @close="$emit('close')" size="large">
    <div class="task-modal">
      <!-- Шапка -->
      <div class="modal-header">
        <div class="task-key-type">
          <span class="task-key">{{ task.key }}</span>
          <TaskTypeIcon :type="task.type" />
        </div>

        <div class="actions">
          <button @click="editTask">✏️ Редактировать</button>
          <button @click="deleteTask">🗑️ Удалить</button>
        </div>
      </div>

      <!-- Основная часть -->
      <div class="modal-body">
        <!-- Левая колонка (контент) -->
        <div class="content-column">
          <!-- Заголовок -->
          <h2>{{ task.title }}</h2>

          <!-- Описание -->
          <div v-if="task.description" class="description">
            <h3>Описание</h3>
            <div v-html="renderMarkdown(task.description)"></div>
          </div>

          <!-- Вложения -->
          <div v-if="task.attachments?.length" class="attachments">
            <h3>Вложения</h3>
            <AttachmentsList :attachments="task.attachments" />
          </div>

          <!-- Подзадачи -->
          <div v-if="task.subtasks?.length" class="subtasks">
            <h3>Подзадачи</h3>
            <SubtasksList
              :subtasks="task.subtasks"
              @subtask-created="reloadTask"
            />
          </div>

          <!-- История активности -->
          <div class="activity">
            <h3>История</h3>
            <ActivityLog :activities="task.activities" />
          </div>

          <!-- Комментарии -->
          <div class="comments">
            <h3>Комментарии</h3>
            <CommentsList
              :comments="task.comments"
              @comment-added="reloadTask"
            />
          </div>
        </div>

        <!-- Правая колонка (метаданные) -->
        <div class="metadata-column">
          <!-- Статус -->
          <div class="field">
            <label>Статус</label>
            <select v-model="task.status_id" @change="updateStatus">
              <option v-for="status in statuses" :value="status.id">
                {{ status.name }}
              </option>
            </select>
          </div>

          <!-- Исполнитель -->
          <div class="field">
            <label>Исполнитель</label>
            <UserSelect
              v-model="task.assignee_id"
              :users="projectMembers"
              @change="updateAssignee"
            />
          </div>

          <!-- Приоритет -->
          <div class="field">
            <label>Приоритет</label>
            <PrioritySelect
              v-model="task.priority"
              @change="updatePriority"
            />
          </div>

          <!-- Дедлайн -->
          <div class="field">
            <label>Дедлайн</label>
            <input
              type="date"
              v-model="task.due_date"
              @change="updateDueDate"
            />
          </div>

          <!-- Story Points -->
          <div class="field">
            <label>Story Points</label>
            <input
              type="number"
              v-model="task.story_points"
              min="1" max="8"
              @change="updateStoryPoints"
            />
          </div>

          <!-- Метки -->
          <div class="field">
            <label>Метки</label>
            <LabelsSelect
              v-model="task.labels"
              :available-labels="projectLabels"
              @change="updateLabels"
            />
          </div>

          <!-- Родительская задача -->
          <div v-if="task.parent_task" class="field">
            <label>Родительская задача</label>
            <TaskLink :task="task.parent_task" />
          </div>

          <!-- Связанные задачи -->
          <div v-if="task.linked_tasks?.length" class="field">
            <label>Связанные задачи</label>
            <LinkedTasksList :links="task.linked_tasks" />
          </div>

          <!-- Метаинформация -->
          <div class="metadata">
            <p><strong>Создал:</strong> {{ task.reporter.name }}</p>
            <p><strong>Создана:</strong> {{ formatDate(task.created_at) }}</p>
            <p><strong>Обновлена:</strong> {{ formatDate(task.updated_at) }}</p>
          </div>
        </div>
      </div>
    </div>
  </Modal>
</template>
```

---

## 🔧 Бэкенд (Laravel)

### Модели

```php
// app/Models/Project.php
class Project extends Model
{
    protected $fillable = [
        'name', 'key', 'description', 'icon', 'color',
        'owner_id', 'is_active', 'default_assignee_id'
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(TaskStatus::class)->orderBy('position');
    }

    // Получить следующий номер задачи
    public function getNextTaskNumber(): int
    {
        return $this->tasks()->max('task_number') + 1;
    }
}

// app/Models/Task.php
class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id', 'task_number', 'title', 'description',
        'type', 'priority', 'status_id', 'reporter_id', 'assignee_id',
        'parent_task_id', 'due_date', 'story_points', 'estimated_hours',
        'board_position', 'started_at', 'completed_at'
    ];

    protected $casts = [
        'due_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Автоматический ключ задачи
    public function getKeyAttribute(): string
    {
        return $this->project->key . '-' . $this->task_number;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'status_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->latest();
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(TaskLabel::class, 'task_label_assignments');
    }

    public function linkedTasks(): HasMany
    {
        return $this->hasMany(TaskLink::class, 'source_task_id');
    }
}
```

### Контроллеры

```php
// app/Http/Controllers/ProjectController.php
class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with('owner')
            ->where('is_active', true)
            ->get();

        return Inertia::render('Tasks/Projects/Index', [
            'projects' => $projects
        ]);
    }

    public function show(Project $project)
    {
        // Проверка доступа
        $this->authorize('view', $project);

        // Kanban доска
        $statuses = $project->statuses;

        $tasks = $project->tasks()
            ->with(['assignee', 'labels', 'subtasks'])
            ->whereNull('parent_task_id') // Только основные задачи
            ->orderBy('board_position')
            ->get()
            ->groupBy('status_id');

        return Inertia::render('Tasks/Projects/Show', [
            'project' => $project->load('members'),
            'statuses' => $statuses,
            'tasks' => $tasks,
            'members' => $project->members,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'key' => 'required|max:10|unique:projects|alpha_upper',
            'description' => 'nullable',
            'icon' => 'nullable|max:50',
            'color' => 'nullable|regex:/^#[0-9A-F]{6}$/i',
        ]);

        $project = Project::create([
            ...$validated,
            'owner_id' => auth()->id(),
        ]);

        // Создать дефолтные статусы для проекта
        $this->createDefaultStatuses($project);

        // Добавить создателя как участника
        $project->members()->attach(auth()->id(), ['role' => 'owner']);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Проект создан');
    }
}

// app/Http/Controllers/TaskController.php
class TaskController extends Controller
{
    public function show(Task $task)
    {
        $this->authorize('view', $task);

        $task->load([
            'project',
            'status',
            'reporter',
            'assignee',
            'parentTask',
            'subtasks.assignee',
            'comments.user',
            'attachments',
            'activities.user',
            'labels',
            'linkedTasks.targetTask'
        ]);

        return Inertia::render('Tasks/Tasks/Show', [
            'task' => $task,
            'statuses' => $task->project->statuses,
            'members' => $task->project->members,
            'labels' => $task->project->labels,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|max:500',
            'description' => 'nullable',
            'type' => 'required|in:task,bug,feature,improvement',
            'priority' => 'required|in:critical,high,medium,low',
            'assignee_id' => 'nullable|exists:users,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'due_date' => 'nullable|date',
            'story_points' => 'nullable|integer|min:1|max:8',
        ]);

        $project = Project::findOrFail($validated['project_id']);
        $this->authorize('createTask', $project);

        // Получить начальный статус
        $initialStatus = $project->statuses()
            ->where('is_initial', true)
            ->first();

        $task = Task::create([
            ...$validated,
            'task_number' => $project->getNextTaskNumber(),
            'status_id' => $initialStatus->id,
            'reporter_id' => auth()->id(),
        ]);

        // Логировать создание
        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'created',
        ]);

        return redirect()->route('tasks.show', $task)
            ->with('success', "Задача {$task->key} создана");
    }

    public function updateStatus(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'status_id' => 'required|exists:task_statuses,id',
            'board_position' => 'nullable|integer',
        ]);

        $oldStatus = $task->status;

        $task->update($validated);

        // Логировать изменение
        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'status_changed',
            'field' => 'status',
            'old_value' => json_encode(['id' => $oldStatus->id, 'name' => $oldStatus->name]),
            'new_value' => json_encode(['id' => $task->status->id, 'name' => $task->status->name]),
        ]);

        return back()->with('success', 'Статус обновлен');
    }
}
```

### Policies (права доступа)

```php
// app/Policies/ProjectPolicy.php
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->members()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, Project $project): bool
    {
        return $project->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id;
    }

    public function createTask(User $user, Project $project): bool
    {
        return $project->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin', 'member'])
            ->exists();
    }
}

// app/Policies/TaskPolicy.php
class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $task->project->members()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function update(User $user, Task $task): bool
    {
        // Может редактировать: создатель, исполнитель, админы проекта
        if ($task->reporter_id === $user->id || $task->assignee_id === $user->id) {
            return true;
        }

        return $task->project->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, Task $task): bool
    {
        // Может удалять: создатель или админы проекта
        if ($task->reporter_id === $user->id) {
            return true;
        }

        return $task->project->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }
}
```

---

## 🔄 API Routes

```php
// routes/web.php

Route::middleware(['auth'])->group(function () {
    // Проекты
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('/create', [ProjectController::class, 'create'])->name('create');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::get('/{project:key}', [ProjectController::class, 'show'])->name('show');
        Route::get('/{project:key}/edit', [ProjectController::class, 'edit'])->name('edit');
        Route::put('/{project:key}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/{project:key}', [ProjectController::class, 'destroy'])->name('destroy');

        // Настройки проекта
        Route::get('/{project:key}/settings', [ProjectController::class, 'settings'])->name('settings');
        Route::post('/{project:key}/members', [ProjectMemberController::class, 'store'])->name('members.store');
        Route::delete('/{project:key}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('members.destroy');
    });

    // Задачи
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/{task}', [TaskController::class, 'show'])->name('show');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::put('/{task}', [TaskController::class, 'update'])->name('update');
        Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');

        // Быстрые действия
        Route::post('/{task}/status', [TaskController::class, 'updateStatus'])->name('update-status');
        Route::post('/{task}/assignee', [TaskController::class, 'updateAssignee'])->name('update-assignee');
        Route::post('/{task}/priority', [TaskController::class, 'updatePriority'])->name('update-priority');

        // Комментарии
        Route::post('/{task}/comments', [TaskCommentController::class, 'store'])->name('comments.store');
        Route::delete('/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('comments.destroy');

        // Вложения
        Route::post('/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('attachments.store');
        Route::delete('/attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])->name('attachments.destroy');
    });
});
```

---

## ✅ MVP Scope (Что реализуем в первую очередь)

### Этап 1: Базовая структура (1-2 дня)
- [ ] Миграции БД (projects, tasks, task_statuses, project_members)
- [ ] Модели и связи
- [ ] Policies (права доступа)
- [ ] Seeders для тестовых данных

### Этап 2: CRUD проектов (1 день)
- [ ] Список проектов
- [ ] Создание проекта
- [ ] Редактирование проекта
- [ ] Удаление проекта

### Этап 3: Kanban доска (2-3 дня)
- [ ] Отображение колонок статусов
- [ ] Карточки задач
- [ ] Drag & Drop между статусами
- [ ] Фильтрация задач

### Этап 4: CRUD задач (2 дня)
- [ ] Создание задачи
- [ ] Просмотр задачи (детальная страница)
- [ ] Редактирование задачи
- [ ] Удаление задачи
- [ ] Изменение статуса
- [ ] Назначение исполнителя

### Этап 5: Комментарии и активность (1 день)
- [ ] Добавление комментариев
- [ ] История изменений
- [ ] Уведомления (опционально)

### Этап 6: Вложения и метки (1 день)
- [ ] Загрузка файлов
- [ ] Создание меток
- [ ] Назначение меток

**Итого:** ~7-10 дней разработки для MVP

---

## 🎨 UI/UX концепция

### Цветовая схема

**Приоритеты:**
- 🔴 **Critical** - `#e74c3c` (красный)
- 🟠 **High** - `#e67e22` (оранжевый)
- 🟡 **Medium** - `#f39c12` (желтый)
- 🟢 **Low** - `#95a5a6` (серый)

**Типы задач:**
- 📋 **Task** - обычная задача
- 🐛 **Bug** - ошибка/баг
- ⭐ **Feature** - новая функция
- 🔧 **Improvement** - улучшение

**Статусы (дефолт):**
- 📦 **Backlog** - серый
- 📝 **To Do** - синий
- ⚙️ **In Progress** - оранжевый
- 👀 **In Review** - фиолетовый
- ✅ **Done** - зеленый

---

## 📦 Что дальше?

После обсуждения и одобрения архитектуры:

1. **Создам миграции БД**
2. **Создам модели Laravel**
3. **Реализую контроллеры**
4. **Создам Vue компоненты для Kanban**
5. **Добавлю Drag & Drop функционал**

---

**Вопросы:**

1. ✅ Архитектура понятна? Что-то добавить/убрать?
2. ✅ MVP scope адекватный? Может что-то упростить?
3. ✅ Начинаем с реализации или еще доработать дизайн?
