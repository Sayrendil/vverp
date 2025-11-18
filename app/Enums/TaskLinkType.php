<?php

namespace App\Enums;

enum TaskLinkType: string
{
    case BLOCKS = 'blocks';
    case RELATES = 'relates';
    case DUPLICATES = 'duplicates';
    case DEPENDS_ON = 'depends_on';

    /**
     * Получить название типа связи
     */
    public function label(): string
    {
        return match($this) {
            self::BLOCKS => 'Блокирует',
            self::RELATES => 'Связана с',
            self::DUPLICATES => 'Дублирует',
            self::DEPENDS_ON => 'Зависит от',
        };
    }

    /**
     * Получить обратную связь
     */
    public function inverse(): string
    {
        return match($this) {
            self::BLOCKS => 'Заблокирована',
            self::RELATES => 'Связана с',
            self::DUPLICATES => 'Дублируется',
            self::DEPENDS_ON => 'Требуется для',
        };
    }
}
