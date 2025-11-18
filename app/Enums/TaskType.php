<?php

namespace App\Enums;

enum TaskType: string
{
    case TASK = 'task';
    case BUG = 'bug';
    case FEATURE = 'feature';
    case IMPROVEMENT = 'improvement';

    /**
     * Получить иконку для типа задачи
     */
    public function icon(): string
    {
        return match($this) {
            self::TASK => '📋',
            self::BUG => '🐛',
            self::FEATURE => '⭐',
            self::IMPROVEMENT => '🔧',
        };
    }

    /**
     * Получить название типа
     */
    public function label(): string
    {
        return match($this) {
            self::TASK => 'Задача',
            self::BUG => 'Баг',
            self::FEATURE => 'Функция',
            self::IMPROVEMENT => 'Улучшение',
        };
    }

    /**
     * Получить цвет типа
     */
    public function color(): string
    {
        return match($this) {
            self::TASK => '#3498db',
            self::BUG => '#e74c3c',
            self::FEATURE => '#9b59b6',
            self::IMPROVEMENT => '#1abc9c',
        };
    }
}
