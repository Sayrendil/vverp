<?php

namespace App\Enums;

enum TaskPriority: string
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

    /**
     * Получить иконку для приоритета
     */
    public function icon(): string
    {
        return match($this) {
            self::CRITICAL => '🔴',
            self::HIGH => '🟠',
            self::MEDIUM => '🟡',
            self::LOW => '🟢',
        };
    }

    /**
     * Получить название приоритета
     */
    public function label(): string
    {
        return match($this) {
            self::CRITICAL => 'Критический',
            self::HIGH => 'Высокий',
            self::MEDIUM => 'Средний',
            self::LOW => 'Низкий',
        };
    }

    /**
     * Получить цвет приоритета
     */
    public function color(): string
    {
        return match($this) {
            self::CRITICAL => '#e74c3c',
            self::HIGH => '#e67e22',
            self::MEDIUM => '#f39c12',
            self::LOW => '#95a5a6',
        };
    }

    /**
     * Получить числовое значение для сортировки
     */
    public function value(): int
    {
        return match($this) {
            self::CRITICAL => 4,
            self::HIGH => 3,
            self::MEDIUM => 2,
            self::LOW => 1,
        };
    }
}
