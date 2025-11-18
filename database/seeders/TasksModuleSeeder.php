<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskLabel;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class TasksModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Создать глобальные статусы для Kanban
        $this->createGlobalStatuses();

        // 2. Создать тестовый проект
        $project = $this->createTestProject();

        // 3. Создать глобальные метки
        $this->createGlobalLabels();

        // 4. Создать тестовые задачи
        $this->createTestTasks($project);

        $this->command->info('✅ Tasks Module seeded successfully!');
    }

    /**
     * Создать глобальные статусы
     */
    private function createGlobalStatuses(): void
    {
        $statuses = [
            [
                'name' => 'Бэклог',
                'slug' => 'backlog',
                'color' => '#95a5a6',
                'position' => 1,
                'is_initial' => true,
                'is_final' => false,
            ],
            [
                'name' => 'К выполнению',
                'slug' => 'to_do',
                'color' => '#3498db',
                'position' => 2,
                'is_initial' => false,
                'is_final' => false,
            ],
            [
                'name' => 'В работе',
                'slug' => 'in_progress',
                'color' => '#f39c12',
                'position' => 3,
                'is_initial' => false,
                'is_final' => false,
            ],
            [
                'name' => 'На проверке',
                'slug' => 'in_review',
                'color' => '#9b59b6',
                'position' => 4,
                'is_initial' => false,
                'is_final' => false,
            ],
            [
                'name' => 'Готово',
                'slug' => 'done',
                'color' => '#27ae60',
                'position' => 5,
                'is_initial' => false,
                'is_final' => true,
            ],
        ];

        foreach ($statuses as $status) {
            TaskStatus::updateOrCreate(
                ['slug' => $status['slug'], 'project_id' => null],
                $status
            );
        }

        $this->command->info('  ✓ Global statuses created');
    }

    /**
     * Создать тестовый проект
     */
    private function createTestProject(): Project
    {
        // Получить первого админа
        $admin = User::where('role', 'admin')->first();

        if (!$admin) {
            $this->command->warn('  ! No admin user found. Using first user.');
            $admin = User::first();
        }

        $project = Project::create([
            'name' => 'VVERP Development',
            'key' => 'VVERP',
            'description' => 'Основной проект разработки системы VVERP. Здесь отслеживаются все задачи по доработке, улучшению и автоматизации системы.',
            'icon' => '🚀',
            'color' => '#3498db',
            'owner_id' => $admin->id,
            'is_active' => true,
        ]);

        // Добавить админа как участника
        $project->members()->attach($admin->id, ['role' => 'owner']);

        // Добавить других пользователей как участников
        $otherUsers = User::where('id', '!=', $admin->id)->take(3)->get();
        foreach ($otherUsers as $user) {
            $project->members()->attach($user->id, ['role' => 'member']);
        }

        $this->command->info("  ✓ Project '{$project->name}' created");

        return $project;
    }

    /**
     * Создать глобальные метки
     */
    private function createGlobalLabels(): void
    {
        $labels = [
            ['name' => 'Срочно', 'color' => '#e74c3c'],
            ['name' => 'Frontend', 'color' => '#3498db'],
            ['name' => 'Backend', 'color' => '#2ecc71'],
            ['name' => 'Database', 'color' => '#f39c12'],
            ['name' => 'UI/UX', 'color' => '#9b59b6'],
            ['name' => 'Документация', 'color' => '#1abc9c'],
            ['name' => 'Тестирование', 'color' => '#e67e22'],
        ];

        foreach ($labels as $label) {
            TaskLabel::create($label);
        }

        $this->command->info('  ✓ Global labels created');
    }

    /**
     * Создать тестовые задачи
     */
    private function createTestTasks(Project $project): void
    {
        $statuses = TaskStatus::whereNull('project_id')->pluck('id', 'slug');
        $users = $project->members()->get();
        $labels = TaskLabel::whereNull('project_id')->get();

        // Задача 1: В бэклоге
        $task1 = Task::create([
            'project_id' => $project->id,
            'title' => 'Добавить экспорт отчетов в Excel',
            'description' => '## Описание
Нужно добавить функционал экспорта отчетов в Excel формате.

## Требования
- Экспорт списка заявок
- Экспорт статистики
- Фильтрация данных перед экспортом

## Технические детали
Использовать библиотеку `maatwebsite/excel`',
            'type' => TaskType::FEATURE,
            'priority' => TaskPriority::MEDIUM,
            'status_id' => $statuses['backlog'],
            'reporter_id' => $users->first()->id,
            'due_date' => now()->addDays(14),
            'story_points' => 5,
        ]);
        $task1->labels()->attach($labels->where('name', 'Backend')->first());

        // Задача 2: К выполнению
        $task2 = Task::create([
            'project_id' => $project->id,
            'title' => 'Исправить баг с уведомлениями в Telegram',
            'description' => '## Проблема
При создании заявки через веб-интерфейс не отправляются вложения в уведомлениях.

## Шаги воспроизведения
1. Создать заявку через веб с фото
2. Проверить уведомление в Telegram
3. Фото отсутствует

## Решение
Добавить fallback для генерации публичных URL',
            'type' => TaskType::BUG,
            'priority' => TaskPriority::HIGH,
            'status_id' => $statuses['to_do'],
            'reporter_id' => $users->first()->id,
            'assignee_id' => $users->count() > 1 ? $users->get(1)->id : null,
            'due_date' => now()->addDays(3),
            'story_points' => 3,
        ]);
        $task2->labels()->attach([
            $labels->where('name', 'Backend')->first()->id,
            $labels->where('name', 'Срочно')->first()->id,
        ]);

        // Задача 3: В работе
        $task3 = Task::create([
            'project_id' => $project->id,
            'title' => 'Улучшить UI страницы заявок',
            'description' => '## Цель
Сделать интерфейс более современным и удобным.

## Что нужно
- Обновить карточки заявок
- Добавить фильтры
- Улучшить адаптивность
- Добавить анимации переходов',
            'type' => TaskType::IMPROVEMENT,
            'priority' => TaskPriority::MEDIUM,
            'status_id' => $statuses['in_progress'],
            'reporter_id' => $users->first()->id,
            'assignee_id' => $users->count() > 2 ? $users->get(2)->id : null,
            'started_at' => now()->subDays(2),
            'story_points' => 8,
        ]);
        $task3->labels()->attach([
            $labels->where('name', 'Frontend')->first()->id,
            $labels->where('name', 'UI/UX')->first()->id,
        ]);

        // Подзадача к задаче 3
        $subtask1 = Task::create([
            'project_id' => $project->id,
            'parent_task_id' => $task3->id,
            'title' => 'Обновить дизайн карточек',
            'description' => 'Переработать внешний вид карточек заявок согласно новому дизайну.',
            'type' => TaskType::TASK,
            'priority' => TaskPriority::MEDIUM,
            'status_id' => $statuses['in_progress'],
            'reporter_id' => $users->first()->id,
            'assignee_id' => $task3->assignee_id,
            'story_points' => 3,
        ]);

        $subtask2 = Task::create([
            'project_id' => $project->id,
            'parent_task_id' => $task3->id,
            'title' => 'Добавить фильтры',
            'description' => 'Реализовать фильтрацию по статусу, исполнителю и категории.',
            'type' => TaskType::TASK,
            'priority' => TaskPriority::MEDIUM,
            'status_id' => $statuses['to_do'],
            'reporter_id' => $users->first()->id,
            'assignee_id' => $task3->assignee_id,
            'story_points' => 2,
        ]);

        // Задача 4: На проверке
        $task4 = Task::create([
            'project_id' => $project->id,
            'title' => 'Настроить CI/CD pipeline',
            'description' => '## Задача
Настроить автоматический деплой через GitHub Actions.

## Этапы
1. Build Docker образов
2. Тестирование
3. Деплой на production
4. Уведомления в Telegram',
            'type' => TaskType::TASK,
            'priority' => TaskPriority::HIGH,
            'status_id' => $statuses['in_review'],
            'reporter_id' => $users->first()->id,
            'assignee_id' => $users->first()->id,
            'started_at' => now()->subDays(5),
            'story_points' => 5,
        ]);
        $task4->labels()->attach($labels->where('name', 'Backend')->first());

        // Задача 5: Завершена
        $task5 = Task::create([
            'project_id' => $project->id,
            'title' => 'Добавить кнопки управления на странице заявки',
            'description' => 'Добавлены кнопки для админов: назначить исполнителя, изменить статус.',
            'type' => TaskType::FEATURE,
            'priority' => TaskPriority::HIGH,
            'status_id' => $statuses['done'],
            'reporter_id' => $users->first()->id,
            'assignee_id' => $users->first()->id,
            'started_at' => now()->subDays(3),
            'completed_at' => now()->subDay(),
            'story_points' => 5,
        ]);
        $task5->labels()->attach([
            $labels->where('name', 'Frontend')->first()->id,
            $labels->where('name', 'Backend')->first()->id,
        ]);

        $this->command->info("  ✓ Created 5 test tasks with subtasks");
    }
}
