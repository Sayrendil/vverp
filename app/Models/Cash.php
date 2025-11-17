<?php

namespace App\Models;

use App\Contracts\Dictionary;
use App\Models\Concerns\SimpleDictionary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cash extends Model implements Dictionary
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
        return 'Кассы';
    }

    /**
     * Получить описание справочника
     */
    public static function getDictionaryDescription(): string
    {
        return 'Кассовые аппараты и точки обслуживания';
    }

    /**
     * Получить иконку
     */
    public static function getDictionaryIcon(): string
    {
        return '💰';
    }

    /**
     * Тикеты связанные с этой кассой
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
