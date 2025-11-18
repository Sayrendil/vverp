<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ProjectController extends Controller
{
    /**
     * Список всех проектов
     */
    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $projects = Project::with(['owner', 'members'])
            ->forUser($user)
            ->active()
            ->get()
            ->map(function ($project) use ($user) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'key' => $project->key,
                    'description' => $project->description,
                    'icon' => $project->icon,
                    'color' => $project->color,
                    'owner' => $project->owner,
                    'members_count' => $project->members->count(),
                    'tasks_count' => $project->tasks()->count(),
                    'user_role' => $project->getUserRole($user),
                ];
            });

        return Inertia::render('Tasks/Projects/Index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Kanban доска проекта
     */
    public function show(Project $project): Response
    {
        $this->authorize('view', $project);

        /** @var User $user */
        $user = auth()->user();

        // Получить статусы для проекта
        $statuses = $project->statuses()
            ->ordered()
            ->get()
            ->map(function ($status) use ($project) {
                return [
                    'id' => $status->id,
                    'name' => $status->name,
                    'slug' => $status->slug,
                    'color' => $status->color,
                    'position' => $status->position,
                    'tasks_count' => $status->tasks()->where('project_id', $project->id)->mainTasks()->count(),
                ];
            });

        // Получить задачи (только главные, без подзадач)
        $tasks = $project->tasks()
            ->with([
                'assignee',
                'labels',
                'subtasks' => function ($query) {
                    $query->with('status');
                },
            ])
            ->mainTasks()
            ->orderBy('board_position')
            ->get()
            ->groupBy('status_id')
            ->map(function ($taskGroup) {
                return $taskGroup->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'key' => $task->key,
                        'title' => $task->title,
                        'type' => $task->type->value,
                        'priority' => $task->priority->value,
                        'status_id' => $task->status_id,
                        'assignee' => $task->assignee ? [
                            'id' => $task->assignee->id,
                            'name' => $task->assignee->name,
                            'profile_photo_url' => $task->assignee->profile_photo_url,
                        ] : null,
                        'labels' => $task->labels->map(fn($l) => [
                            'id' => $l->id,
                            'name' => $l->name,
                            'color' => $l->color,
                        ]),
                        'subtasks_count' => $task->subtasks->count(),
                        'completed_subtasks_count' => $task->subtasks->filter(fn($st) => $st->status->is_final)->count(),
                        'comments_count' => $task->comments()->count(),
                        'attachments_count' => $task->attachments()->count(),
                        'due_date' => $task->due_date?->format('Y-m-d'),
                        'is_overdue' => $task->isOverdue(),
                    ];
                });
            });

        // Участники проекта для выбора исполнителя
        $members = $project->members()->get()->map(function ($member) {
            return [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'profile_photo_url' => $member->profile_photo_url,
            ];
        });

        // Метки проекта
        $labels = $project->labels()->get()->map(function ($label) {
            return [
                'id' => $label->id,
                'name' => $label->name,
                'color' => $label->color,
            ];
        });

        return Inertia::render('Tasks/Projects/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'key' => $project->key,
                'description' => $project->description,
                'icon' => $project->icon,
                'color' => $project->color,
                'user_role' => $project->getUserRole($user),
            ],
            'statuses' => $statuses,
            'tasks' => $tasks,
            'members' => $members,
            'labels' => $labels,
        ]);
    }

    /**
     * Форма создания проекта
     */
    public function create(): Response
    {
        $this->authorize('create', Project::class);

        return Inertia::render('Tasks/Projects/Create');
    }

    /**
     * Создать новый проект
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:10|unique:projects|regex:/^[A-Z]+$/',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
        ]);

        $project = Project::create([
            ...$validated,
            'owner_id' => auth()->id(),
            'is_active' => true,
        ]);

        // Скопировать глобальные статусы для проекта
        $globalStatuses = TaskStatus::whereNull('project_id')->ordered()->get();
        foreach ($globalStatuses as $status) {
            TaskStatus::create([
                'project_id' => $project->id,
                'name' => $status->name,
                'slug' => $status->slug,
                'color' => $status->color,
                'position' => $status->position,
                'is_initial' => $status->is_initial,
                'is_final' => $status->is_final,
            ]);
        }

        // Добавить создателя как владельца
        $project->members()->attach(auth()->id(), ['role' => 'owner']);

        return redirect()->route('projects.show', $project->key)
            ->with('success', "Проект {$project->name} создан успешно!");
    }

    /**
     * Форма редактирования проекта
     */
    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);

        return Inertia::render('Tasks/Projects/Edit', [
            'project' => $project,
        ]);
    }

    /**
     * Обновить проект
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
        ]);

        $project->update($validated);

        return redirect()->route('projects.show', $project->key)
            ->with('success', 'Проект обновлен успешно!');
    }

    /**
     * Удалить проект
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $projectName = $project->name;
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', "Проект {$projectName} удален.");
    }

    /**
     * Настройки проекта
     */
    public function settings(Project $project): Response
    {
        $this->authorize('updateSettings', $project);

        $members = $project->members()->get()->map(function ($member) use ($project) {
            return [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->pivot->role,
                'profile_photo_url' => $member->profile_photo_url,
            ];
        });

        $statuses = $project->statuses()->ordered()->get();

        return Inertia::render('Tasks/Projects/Settings', [
            'project' => $project,
            'members' => $members,
            'statuses' => $statuses,
        ]);
    }
}
