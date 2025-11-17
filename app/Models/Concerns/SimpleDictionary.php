<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait для простых справочников
 *
 * Предоставляет базовую реализацию для справочников
 * с простой структурой (id + name)
 */
trait SimpleDictionary
{
    /**
     * Получить название справочника в единственном числе
     */
    public static function getDictionarySingularName(): string
    {
        return rtrim(static::getDictionaryName(), 'ыи') . (
            str_ends_with(static::getDictionaryName(), 'ы') ? '' : 'а'
        );
    }

    /**
     * Получить ключ справочника для URL
     */
    public static function getDictionaryKey(): string
    {
        return str(class_basename(static::class))->snake()->plural()->toString();
    }

    /**
     * Правила валидации для создания
     */
    public static function getCreateValidationRules(): array
    {
        $tableName = (new static)->getTable();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                "unique:{$tableName},name",
            ],
        ];
    }

    /**
     * Правила валидации для обновления
     */
    public static function getUpdateValidationRules(int $id): array
    {
        $tableName = (new static)->getTable();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                "unique:{$tableName},name,{$id}",
            ],
        ];
    }

    /**
     * Получить поля для таблицы
     */
    public static function getTableColumns(): array
    {
        return [
            [
                'key' => 'id',
                'label' => 'ID',
                'sortable' => true,
                'width' => '80px',
            ],
            [
                'key' => 'name',
                'label' => 'Название',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'created_at',
                'label' => 'Создано',
                'sortable' => true,
                'type' => 'datetime',
                'width' => '180px',
            ],
        ];
    }

    /**
     * Получить поля формы
     */
    public static function getFormFields(): array
    {
        return [
            [
                'key' => 'name',
                'label' => 'Название',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Введите название',
                'help' => 'Уникальное название для справочника',
            ],
        ];
    }

    /**
     * Проверка возможности удаления
     */
    public function canBeDeleted(): bool
    {
        // Проверяем связи с другими таблицами
        $relations = $this->getProtectedRelations();

        foreach ($relations as $relation) {
            if (method_exists($this, $relation)) {
                if ($this->$relation()->exists()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Получить причину блокировки удаления
     */
    public function getDeleteRestrictionReason(): ?string
    {
        $relations = $this->getProtectedRelations();

        foreach ($relations as $relation => $label) {
            if (method_exists($this, $relation)) {
                $count = $this->$relation()->count();
                if ($count > 0) {
                    return "Нельзя удалить, есть связанные записи: {$label} ({$count})";
                }
            }
        }

        return null;
    }

    /**
     * Получить список защищенных связей
     * Переопределите в модели для указания своих связей
     */
    protected function getProtectedRelations(): array
    {
        return [];
    }

    /**
     * Получить данные для select
     */
    public static function forSelect(): array
    {
        return static::query()
            ->orderBy(...array_keys(static::getDefaultOrder()))
            ->get()
            ->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Порядок сортировки по умолчанию
     */
    public static function getDefaultOrder(): array
    {
        return ['name' => 'asc'];
    }

    /**
     * Scope для поиска по названию
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where('name', 'like', "%{$search}%");
    }
}
