<?php

namespace App\Models;

use App\Contracts\Dictionary;
use App\Models\Concerns\SimpleDictionary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model implements Dictionary
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
        return 'Магазины';
    }

    /**
     * Получить описание справочника
     */
    public static function getDictionaryDescription(): string
    {
        return 'Список торговых точек и магазинов';
    }

    /**
     * Получить иконку
     */
    public static function getDictionaryIcon(): string
    {
        return '🏪';
    }

    /**
     * Тикеты этого магазина
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Пользователи привязанные к этому магазину
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Хосты для мониторинга этого магазина
     */
    public function hosts(): HasMany
    {
        return $this->hasMany(Host::class);
    }

    /**
     * Защищенные связи для проверки при удалении
     */
    protected function getProtectedRelations(): array
    {
        return [
            'tickets' => 'Заявки',
            'users' => 'Пользователи',
            'hosts' => 'Хосты мониторинга',
        ];
    }
}
