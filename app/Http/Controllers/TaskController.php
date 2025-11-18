<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class TaskController extends Controller
{
    /**
     * Детальная страница задачи
     */
    public function show(Task $task): Response
    {
        $this->authorize('view', $task);

        $task->load([
            'project',
            'status',
            'reporter',
            'assignee',
            'parentTask',
            'subtasks.assignee',
            'subtasks.status',
            'comments.user',
            'attachments.user',
            'activities.user',
            'labels',
            'linkedTasks.targetTask',
        ]);

        return Inertia::render('Tasks/Tasks/Show', [
            'task' => [
                'id' => $task->id,
                'key' => $task->key,
                'title' => $task->title,
                'description' => $task->description,
                'type' => $task->type->value,
                'priority' => $task->priority->value,
                'status_id' => $task->status_id,
                'status' => [
                    'id' => $task->status->id,
                    'name' => $task->status->name,
                    'color' => $task->status->color,
                ],
                'reporter' => [
                    'id' => $task->reporter->id,
                    'name' => $task->reporter->name,
                    'profile_photo_url' => $task->reporter->profile_photo_url,
                ],
                'assignee' => $task->assignee ? [
                    'id' => $task->assignee->id,
                    'name' => $task->assignee->name,
                    'profile_photo_url' => $task->assignee->profile_photo_url,
                ] : null,
                'parent_task' => $task->parentTask ? [
                    'id' => $task->parentTask->id,
                    'key' => $task->parentTask->key,
                    'title' => $task->parentTask->title,
                ] : null,
                'subtasks' => $task->subtasks->map(fn($st) => [
                    'id' => $st->id,
                    'key' => $st->key,
                    'title' => $st->title,
                    'status' => [
                        'id' => $st->status->id,
                        'name' => $st->status->name,
                        'is_final' => $st->status->is_final,
                    ],
                    'assignee' => $st->assignee ? [
                        'id' => $st->assignee->id,
                        'name' => $st->assignee->name,
                    ] : null,
                ]),
                'comments' => $task->comments->map(fn($c) => [
                    'id' => $c->id,
                    'content' => $c->content,
                    'user' => [
                        'id' => $c->user->id,
                        'name' => $c->user->name,
                        'profile_photo_url' => $c->user->profile_photo_url,
                    ],
                    'created_at' => $c->created_at->format('Y-m-d H:i'),
                ]),
                'attachments' => $task->attachments->map(fn($a) => [
                    'id' => $a->id,
                    'file_name' => $a->file_name,
                    'file_size' => $a->formatted_size,
                    'file_type' => $a->file_type,
                    'url' => $a->url,
                    'user' => [
                        'name' => $a->user->name,
                    ],
                    'created_at' => $a->created_at->format('Y-m-d H:i'),
                ]),
                'activities' => $task->activities->map(fn($a) => [
                    'id' => $a->id,
                    'description' => $a->description,
                    'action' => $a->action,
                    'field' => $a->field,
                    'old_value' => $a->old_value_decoded,
                    'new_value' => $a->new_value_decoded,
                    'user' => $a->user ? [
                        'id' => $a->user->id,
                        'name' => $a->user->name,
                    ] : null,
                    'created_at' => $a->created_at->format('Y-m-d H:i'),
                ]),
                'labels' => $task->labels->map(fn($l) => [
                    'id' => $l->id,
                    'name' => $l->name,
                    'color' => $l->color,
                ]),
                'linked_tasks' => $task->linkedTasks->map(fn($l) => [
                    'id' => $l->id,
                    'link_type' => $l->link_type->value,
                    'target_task' => [
                        'id' => $l->targetTask->id,
                        'key' => $l->targetTask->key,
                        'title' => $l->targetTask->title,
                    ],
                ]),
                'due_date' => $task->due_date?->format('Y-m-d'),
                'story_points' => $task->story_points,
                'estimated_hours' => $task->estimated_hours,
                'created_at' => $task->created_at->format('Y-m-d H:i'),
                'updated_at' => $task->updated_at->format('Y-m-d H:i'),
            ],
            'project' => [
                'id' => $task->project->id,
                'name' => $task->project->name,
                'key' => $task->project->key,
            ],
            'statuses' => $task->project->statuses()->ordered()->get(),
            'members' => $task->project->members()->get(),
            'labels' => $task->project->labels()->get(),
        ]);
    }

    /**
     * Создать новую задачу
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:500',
            'description' => 'nullable|string',
            'type' => 'required|in:task,bug,feature,improvement',
            'priority' => 'required|in:critical,high,medium,low',
            'assignee_id' => 'nullable|exists:users,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'due_date' => 'nullable|date',
            'story_points' => 'nullable|integer|min:1|max:13',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);

        $project = Project::findOrFail($validated['project_id']);
        $this->authorize('createTask', $project);

        // Получить начальный статус
        $initialStatus = $project->statuses()->where('is_initial', true)->first();

        if (!$initialStatus) {
            $initialStatus = $project->statuses()->ordered()->first();
        }

        $task = Task::create([
            ...$validated,
            'status_id' => $initialStatus->id,
            'reporter_id' => auth()->id(),
        ]);

        // Логировать создание
        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'created',
        ]);

        return redirect()->route('tasks.show', $task->id)
            ->with('success', "Задача {$task->key} создана успешно!");
    }

    /**
     * Обновить задачу
     */
    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:500',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:task,bug,feature,improvement',
            'priority' => 'sometimes|required|in:critical,high,medium,low',
            'due_date' => 'nullable|date',
            'story_points' => 'nullable|integer|min:1|max:13',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);

        // Отследить изменения для логирования
        $changes = [];
        foreach ($validated as $key => $value) {
            if ($task->{$key} != $value) {
                $changes[$key] = [
                    'old' => $task->{$key},
                    'new' => $value,
                ];
            }
        }

        $task->update($validated);

        // Логировать изменения
        foreach ($changes as $field => $change) {
            TaskActivity::create([
                'task_id' => $task->id,
                'user_id' => auth()->id(),
                'action' => 'updated',
                'field' => $field,
                'old_value' => json_encode($change['old']),
                'new_value' => json_encode($change['new']),
            ]);
        }

        return back()->with('success', 'Задача обновлена успешно!');
    }

    /**
     * Удалить задачу
     */
    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $projectKey = $task->project->key;
        $taskKey = $task->key;

        // Логировать удаление
        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'deleted',
        ]);

        $task->delete();

        return redirect()->route('projects.show', $projectKey)
            ->with('success', "Задача {$taskKey} удалена.");
    }

    /**
     * Изменить статус задачи (для drag & drop на Kanban доске)
     */
    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('updateStatus', $task);

        $validated = $request->validate([
            'status_id' => 'required|exists:task_statuses,id',
            'board_position' => 'nullable|integer',
        ]);

        $oldStatus = $task->status;
        $newStatus = TaskStatus::find($validated['status_id']);

        $task->update([
            'status_id' => $validated['status_id'],
            'board_position' => $validated['board_position'] ?? $task->board_position,
        ]);

        // Установить started_at если задача переведена в "В работе"
        if ($newStatus->slug === 'in_progress' && !$task->started_at) {
            $task->update(['started_at' => now()]);
        }

        // Установить completed_at если задача завершена
        if ($newStatus->is_final && !$task->completed_at) {
            $task->update(['completed_at' => now()]);
        }

        // Логировать изменение статуса
        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'status_changed',
            'field' => 'status',
            'old_value' => json_encode(['id' => $oldStatus->id, 'name' => $oldStatus->name]),
            'new_value' => json_encode(['id' => $newStatus->id, 'name' => $newStatus->name]),
        ]);

        return back()->with('success', 'Статус задачи изменен!');
    }

    /**
     * Изменить исполнителя
     */
    public function updateAssignee(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('updateAssignee', $task);

        $validated = $request->validate([
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        $oldAssignee = $task->assignee;
        $task->update($validated);

        // Логировать изменение
        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'assignee_changed',
            'field' => 'assignee',
            'old_value' => $oldAssignee ? json_encode(['id' => $oldAssignee->id, 'name' => $oldAssignee->name]) : null,
            'new_value' => $task->assignee ? json_encode(['id' => $task->assignee->id, 'name' => $task->assignee->name]) : null,
        ]);

        return back()->with('success', 'Исполнитель назначен!');
    }

    /**
     * Изменить приоритет
     */
    public function updatePriority(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'priority' => 'required|in:critical,high,medium,low',
        ]);

        $oldPriority = $task->priority;
        $task->update($validated);

        // Логировать изменение
        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'priority_changed',
            'field' => 'priority',
            'old_value' => json_encode($oldPriority->value),
            'new_value' => json_encode($validated['priority']),
        ]);

        return back()->with('success', 'Приоритет изменен!');
    }
}
