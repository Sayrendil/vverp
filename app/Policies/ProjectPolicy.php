<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Может ли пользователь просматривать список проектов
     */
    public function viewAny(User $user): bool
    {
        return true; // Все авторизованные пользователи могут видеть список
    }

    /**
     * Может ли пользователь просматривать конкретный проект
     */
    public function view(User $user, Project $project): bool
    {
        return $project->hasMember($user);
    }

    /**
     * Может ли пользователь создавать проекты
     */
    public function create(User $user): bool
    {
        // Только админы могут создавать проекты
        return $user->isAdmin();
    }

    /**
     * Может ли пользователь редактировать проект
     */
    public function update(User $user, Project $project): bool
    {
        $role = $project->getUserRole($user);

        if (!$role) {
            return false;
        }

        $projectRole = ProjectRole::from($role);
        return $projectRole->canEditProject();
    }

    /**
     * Может ли пользователь удалять проект
     */
    public function delete(User $user, Project $project): bool
    {
        // Только владелец проекта может его удалить
        return $project->owner_id === $user->id;
    }

    /**
     * Может ли пользователь управлять участниками проекта
     */
    public function manageMembers(User $user, Project $project): bool
    {
        $role = $project->getUserRole($user);

        if (!$role) {
            return false;
        }

        $projectRole = ProjectRole::from($role);
        return $projectRole->canManageMembers();
    }

    /**
     * Может ли пользователь создавать задачи в проекте
     */
    public function createTask(User $user, Project $project): bool
    {
        $role = $project->getUserRole($user);

        if (!$role) {
            return false;
        }

        $projectRole = ProjectRole::from($role);
        return $projectRole->canCreateTasks();
    }

    /**
     * Может ли пользователь изменять настройки проекта
     */
    public function updateSettings(User $user, Project $project): bool
    {
        $role = $project->getUserRole($user);

        if (!$role) {
            return false;
        }

        $projectRole = ProjectRole::from($role);
        return $projectRole->canEditProject();
    }
}
