<?php

namespace App\Models;

use App\Enums\TaskLinkType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskLink extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'source_task_id',
        'target_task_id',
        'link_type',
    ];

    protected $casts = [
        'link_type' => TaskLinkType::class,
        'created_at' => 'datetime',
    ];

    /**
     * Исходная задача
     */
    public function sourceTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'source_task_id');
    }

    /**
     * Целевая задача
     */
    public function targetTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'target_task_id');
    }
}
