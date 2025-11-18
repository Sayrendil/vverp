<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskActivity extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'user_id',
        'action',
        'field',
        'old_value',
        'new_value',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Задача
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Пользователь выполнивший действие
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить декодированное старое значение
     */
    public function getOldValueDecodedAttribute()
    {
        return $this->old_value ? json_decode($this->old_value, true) : null;
    }

    /**
     * Получить декодированное новое значение
     */
    public function getNewValueDecodedAttribute()
    {
        return $this->new_value ? json_decode($this->new_value, true) : null;
    }

    /**
     * Получить описание действия
     */
    public function getDescriptionAttribute(): string
    {
        $userName = $this->user?->name ?? 'Система';

        return match($this->action) {
            'created' => "{$userName} создал задачу",
            'updated' => "{$userName} обновил задачу",
            'status_changed' => "{$userName} изменил статус",
            'assignee_changed' => "{$userName} изменил исполнителя",
            'priority_changed' => "{$userName} изменил приоритет",
            'commented' => "{$userName} оставил комментарий",
            'attached' => "{$userName} добавил вложение",
            'deleted' => "{$userName} удалил задачу",
            default => "{$userName} выполнил действие: {$this->action}",
        };
    }
}
