<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TaskLabel extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'color',
    ];

    /**
     * Проект метки (NULL для глобальных меток)
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Задачи с этой меткой
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_label_assignments', 'label_id', 'task_id')
            ->withTimestamps();
    }

    /**
     * Scope для глобальных меток
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('project_id');
    }

    /**
     * Scope для меток проекта
     */
    public function scopeForProject($query, Project $project)
    {
        return $query->where('project_id', $project->id)
            ->orWhereNull('project_id');
    }
}
