<?php

namespace App\Models;

use App\Contracts\Dictionary;
use App\Models\Concerns\SimpleDictionary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model implements Dictionary
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
        return 'Категории заявок';
    }

    /**
     * Получить описание справочника
     */
    public static function getDictionaryDescription(): string
    {
        return 'Категории для классификации заявок (IT, АХО, Безопасность и т.д.)';
    }

    /**
     * Получить иконку
     */
    public static function getDictionaryIcon(): string
    {
        return '📁';
    }

    /**
     * Тикеты этой категории
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Пользователи этой категории
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Исполнители этой категории
     */
    public function executors(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'category_executors'
        )->withPivot(['is_active', 'priority', 'max_tickets'])
          ->withTimestamps()
          ->withCasts([
              'is_active' => 'boolean',
              'priority' => 'integer',
              'max_tickets' => 'integer',
          ]);
    }

    /**
     * Активные исполнители категории
     */
    public function activeExecutors(): BelongsToMany
    {
        return $this->executors()->wherePivot('is_active', true);
    }

    /**
     * Защищенные связи для проверки при удалении
     */
    protected function getProtectedRelations(): array
    {
        return [
            'tickets' => 'Заявки',
            'users' => 'Пользователи',
            'executors' => 'Исполнители',
        ];
    }
}
