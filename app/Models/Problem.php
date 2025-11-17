<?php

namespace App\Models;

use App\Contracts\Dictionary;
use App\Models\Concerns\SimpleDictionary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Problem extends Model implements Dictionary
{
    use SimpleDictionary;

    protected $fillable = [
        'name',
    ];

    /**
     * Получить название справочника
     */
    public static function getDictionaryName(): string
    {
        return 'Проблемы';
    }

    /**
     * Получить описание справочника
     */
    public static function getDictionaryDescription(): string
    {
        return 'Типы проблем и неисправностей';
    }

    /**
     * Получить иконку
     */
    public static function getDictionaryIcon(): string
    {
        return '🔧';
    }

    /**
     * Тикеты с этой проблемой
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Защищенные связи для проверки при удалении
     */
    protected function getProtectedRelations(): array
    {
        return [
            'tickets' => 'Заявки',
        ];
    }
}
