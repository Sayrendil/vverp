<?php

namespace App\Enums;

enum ProjectRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';
    case VIEWER = 'viewer';

    /**
     * Получить название роли
     */
    public function label(): string
    {
        return match($this) {
            self::OWNER => 'Владелец',
            self::ADMIN => 'Администратор',
            self::MEMBER => 'Участник',
            self::VIEWER => 'Наблюдатель',
        };
    }

    /**
     * Может ли создавать задачи
     */
    public function canCreateTasks(): bool
    {
        return match($this) {
            self::OWNER, self::ADMIN, self::MEMBER => true,
            self::VIEWER => false,
        };
    }

    /**
     * Может ли редактировать проект
     */
    public function canEditProject(): bool
    {
        return match($this) {
            self::OWNER, self::ADMIN => true,
            self::MEMBER, self::VIEWER => false,
        };
    }

    /**
     * Может ли управлять участниками
     */
    public function canManageMembers(): bool
    {
        return match($this) {
            self::OWNER, self::ADMIN => true,
            self::MEMBER, self::VIEWER => false,
        };
    }
}
