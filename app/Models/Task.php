<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'task_number',
        'title',
        'description',
        'type',
        'priority',
        'status_id',
        'reporter_id',
        'assignee_id',
        'parent_task_id',
        'due_date',
        'started_at',
        'completed_at',
        'story_points',
        'estimated_hours',
        'board_position',
    ];

    protected $casts = [
        'type' => TaskType::class,
        'priority' => TaskPriority::class,
        'due_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'story_points' => 'integer',
        'estimated_hours' => 'decimal:2',
    ];

    protected $appends = ['key'];

    /**
     * Получить ключ задачи (например VVERP-15)
     */
    public function getKeyAttribute(): string
    {
        return $this->project->key . '-' . $this->task_number;
    }

    /**
     * Проект задачи
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Статус задачи
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'status_id');
    }

    /**
     * Автор задачи
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Исполнитель задачи
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Родительская задача
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Подзадачи
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * Комментарии
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    /**
     * Вложения
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    /**
     * История активности
     */
    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->latest();
    }

    /**
     * Метки
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(TaskLabel::class, 'task_label_assignments', 'task_id', 'label_id')
            ->withTimestamps();
    }

    /**
     * Связанные задачи (исходящие)
     */
    public function linkedTasks(): HasMany
    {
        return $this->hasMany(TaskLink::class, 'source_task_id');
    }

    /**
     * Связанные задачи (входящие)
     */
    public function linkedFromTasks(): HasMany
    {
        return $this->hasMany(TaskLink::class, 'target_task_id');
    }

    /**
     * Проверить завершена ли задача
     */
    public function isCompleted(): bool
    {
        return $this->status?->is_final ?? false;
    }

    /**
     * Проверить просрочена ли задача
     */
    public function isOverdue(): bool
    {
        if (!$this->due_date || $this->isCompleted()) {
            return false;
        }

        return $this->due_date->isPast();
    }

    /**
     * Получить количество завершенных подзадач
     */
    public function getCompletedSubtasksCountAttribute(): int
    {
        return $this->subtasks()
            ->whereHas('status', fn($q) => $q->where('is_final', true))
            ->count();
    }

    /**
     * Получить прогресс выполнения подзадач (0-100)
     */
    public function getSubtasksProgressAttribute(): int
    {
        $total = $this->subtasks()->count();

        if ($total === 0) {
            return 100;
        }

        $completed = $this->completed_subtasks_count;
        return (int) round(($completed / $total) * 100);
    }

    /**
     * Scope для главных задач (не подзадачи)
     */
    public function scopeMainTasks($query)
    {
        return $query->whereNull('parent_task_id');
    }

    /**
     * Scope для задач по статусу
     */
    public function scopeByStatus($query, int $statusId)
    {
        return $query->where('status_id', $statusId);
    }

    /**
     * Scope для задач назначенных пользователю
     */
    public function scopeAssignedTo($query, User $user)
    {
        return $query->where('assignee_id', $user->id);
    }

    /**
     * Scope для задач созданных пользователем
     */
    public function scopeReportedBy($query, User $user)
    {
        return $query->where('reporter_id', $user->id);
    }

    /**
     * Boot метод для автоматической установки task_number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            if (!$task->task_number) {
                $task->task_number = $task->project->getNextTaskNumber();
            }
        });
    }
}
