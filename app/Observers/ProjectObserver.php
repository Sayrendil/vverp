<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\TaskStatus;

class ProjectObserver
{
    /**
     * Handle the Project "created" event.
     */
    public function created(Project $project): void
    {
        // Создать дефолтные статусы для нового проекта
        $this->createDefaultStatuses($project);
    }

    /**
     * Создать дефолтные статусы для проекта
     */
    private function createDefaultStatuses(Project $project): void
    {
        $defaultStatuses = [
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
                'name' => 'Завершено',
                'slug' => 'done',
                'color' => '#2ecc71',
                'position' => 5,
                'is_initial' => false,
                'is_final' => true,
            ],
        ];

        foreach ($defaultStatuses as $status) {
            TaskStatus::create([
                ...$status,
                'project_id' => $project->id,
            ]);
        }
    }
}
