<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Может ли пользователь просматривать задачу
     */
    public function view(User $user, Task $task): bool
    {
        // Может просматривать если является участником проекта
        return $task->project->hasMember($user);
    }

    /**
     * Может ли пользователь создавать задачи
     * (проверяется в ProjectPolicy::createTask)
     */
    public function create(User $user): bool
    {
        return true; // Детальная проверка в ProjectPolicy
    }

    /**
     * Может ли пользователь редактировать задачу
     */
    public function update(User $user, Task $task): bool
    {
        // Не может редактировать завершенные задачи (опционально)
        // if ($task->isCompleted()) {
        //     return false;
        // }

        // Может редактировать если:
        // 1. Он создатель задачи
        if ($task->reporter_id === $user->id) {
            return true;
        }

        // 2. Он исполнитель задачи
        if ($task->assignee_id === $user->id) {
            return true;
        }

        // 3. Он админ или владелец проекта
        $role = $task->project->getUserRole($user);

        if (!$role) {
            return false;
        }

        $projectRole = ProjectRole::from($role);
        return $projectRole->canEditProject();
    }

    /**
     * Может ли пользователь удалять задачу
     */
    public function delete(User $user, Task $task): bool
    {
        // Не может удалять завершенные задачи
        if ($task->isCompleted()) {
            return false;
        }

        // Может удалять если:
        // 1. Он создатель задачи
        if ($task->reporter_id === $user->id) {
            return true;
        }

        // 2. Он админ или владелец проекта
        $role = $task->project->getUserRole($user);

        if (!$role) {
            return false;
        }

        $projectRole = ProjectRole::from($role);
        return $projectRole->canEditProject();
    }

    /**
     * Может ли пользователь изменять статус задачи
     */
    public function updateStatus(User $user, Task $task): bool
    {
        // Может изменять статус если является участником проекта
        // и имеет роль member или выше
        $role = $task->project->getUserRole($user);

        if (!$role) {
            return false;
        }

        $projectRole = ProjectRole::from($role);
        return $projectRole->canCreateTasks(); // member и выше
    }

    /**
     * Может ли пользователь назначать исполнителя
     */
    public function updateAssignee(User $user, Task $task): bool
    {
        // Может назначать если:
        // 1. Он создатель задачи
        if ($task->reporter_id === $user->id) {
            return true;
        }

        // 2. Он текущий исполнитель (может отказаться)
        if ($task->assignee_id === $user->id) {
            return true;
        }

        // 3. Он админ проекта
        $role = $task->project->getUserRole($user);

        if (!$role) {
            return false;
        }

        $projectRole = ProjectRole::from($role);
        return $projectRole->canEditProject();
    }

    /**
     * Может ли пользователь комментировать задачу
     */
    public function comment(User $user, Task $task): bool
    {
        // Может комментировать если является участником проекта
        return $task->project->hasMember($user);
    }

    /**
     * Может ли пользователь добавлять вложения
     */
    public function attach(User $user, Task $task): bool
    {
        // Может добавлять вложения если является участником проекта
        $role = $task->project->getUserRole($user);

        if (!$role) {
            return false;
        }

        $projectRole = ProjectRole::from($role);
        return $projectRole->canCreateTasks(); // member и выше
    }

    /**
     * Может ли пользователь удалять комментарии
     */
    public function deleteComment(User $user, Task $task, $comment): bool
    {
        // Может удалять свои комментарии или если админ проекта
        if ($comment->user_id === $user->id) {
            return true;
        }

        $role = $task->project->getUserRole($user);

        if (!$role) {
            return false;
        }

        $projectRole = ProjectRole::from($role);
        return $projectRole->canEditProject();
    }

    /**
     * Может ли пользователь удалять вложения
     */
    public function deleteAttachment(User $user, Task $task, $attachment): bool
    {
        // Может удалять свои вложения или если админ проекта
        if ($attachment->user_id === $user->id) {
            return true;
        }

        $role = $task->project->getUserRole($user);

        if (!$role) {
            return false;
        }

        $projectRole = ProjectRole::from($role);
        return $projectRole->canEditProject();
    }
}
