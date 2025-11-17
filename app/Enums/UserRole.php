<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case EMPLOYEE = 'employee';
    case AHO_SPECIALIST = 'aho_specialist';

    /**
     * Получить русское название роли
     */
    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Администратор',
            self::EMPLOYEE => 'Сотрудник',
            self::AHO_SPECIALIST => 'АХО специалист',
        };
    }

    /**
     * Получить описание роли
     */
    public function description(): string
    {
        return match($this) {
            self::ADMIN => 'Полный доступ к управлению тикетами своей категории',
            self::EMPLOYEE => 'Может создавать и просматривать свои тикеты',
            self::AHO_SPECIALIST => 'Получает уведомления через Telegram, не имеет доступа к системе',
        };
    }

    /**
     * Проверить, является ли пользователь администратором
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Проверить, является ли пользователь сотрудником
     */
    public function isEmployee(): bool
    {
        return $this === self::EMPLOYEE;
    }

    /**
     * Проверить, является ли пользователь АХО специалистом
     */
    public function isAhoSpecialist(): bool
    {
        return $this === self::AHO_SPECIALIST;
    }

    /**
     * Может ли пользователь с этой ролью входить в систему
     */
    public function canLogin(): bool
    {
        return $this !== self::AHO_SPECIALIST;
    }

    /**
     * Получить все роли для dropdown
     */
    public static function options(): array
    {
        return array_map(
            fn(self $role) => [
                'value' => $role->value,
                'label' => $role->label(),
                'description' => $role->description(),
            ],
            self::cases()
        );
    }
}
