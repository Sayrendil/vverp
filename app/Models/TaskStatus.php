<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskStatus extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'slug',
        'color',
        'position',
        'is_initial',
        'is_final',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_initial' => 'boolean',
        'is_final' => 'boolean',
    ];

    /**
     * Проект статуса (NULL для глобальных статусов)
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Задачи с этим статусом
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'status_id');
    }

    /**
     * Scope для глобальных статусов
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('project_id');
    }

    /**
     * Scope для статусов проекта
     */
    public function scopeForProject($query, Project $project)
    {
        return $query->where('project_id', $project->id)
            ->orWhereNull('project_id');
    }

    /**
     * Упорядочить по позиции
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
