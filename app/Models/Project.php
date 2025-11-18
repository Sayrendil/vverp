<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'key',
        'description',
        'icon',
        'color',
        'owner_id',
        'default_assignee_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Владелец проекта
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Исполнитель по умолчанию
     */
    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    /**
     * Участники проекта
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Задачи проекта
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Статусы проекта
     */
    public function statuses(): HasMany
    {
        return $this->hasMany(TaskStatus::class)->orderBy('position');
    }

    /**
     * Метки проекта
     */
    public function labels(): HasMany
    {
        return $this->hasMany(TaskLabel::class);
    }

    /**
     * Получить следующий номер задачи
     */
    public function getNextTaskNumber(): int
    {
        return ($this->tasks()->max('task_number') ?? 0) + 1;
    }

    /**
     * Проверить является ли пользователь участником проекта
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Получить роль пользователя в проекте
     */
    public function getUserRole(User $user): ?string
    {
        $member = $this->members()->where('user_id', $user->id)->first();
        return $member?->pivot->role;
    }

    /**
     * Scope для активных проектов
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для проектов где пользователь является участником
     */
    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }
}
