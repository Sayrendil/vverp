<?php

namespace App\Services;

use App\Contracts\Dictionary;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Сервис для работы со справочниками
 *
 * Централизованная бизнес-логика для управления справочниками
 */
class DictionaryService
{
    /**
     * Маппинг ключей справочников к классам моделей
     */
    protected array $dictionaryMap = [
        'stores' => \App\Models\Store::class,
        'cashes' => \App\Models\Cash::class,
        'statuses' => \App\Models\Status::class,
        'problems' => \App\Models\Problem::class,
        'ticket_categories' => \App\Models\TicketCategory::class,
        'users' => \App\Models\User::class,
        'hosts' => \App\Models\Host::class,
    ];

    /**
     * Получить список всех доступных справочников
     *
     * @return Collection
     */
    public function getAllDictionaries(): Collection
    {
        return collect($this->dictionaryMap)->map(function ($modelClass, $key) {
            return [
                'key' => $key,
                'name' => $modelClass::getDictionaryName(),
                'singular_name' => $modelClass::getDictionarySingularName(),
                'description' => $modelClass::getDictionaryDescription(),
                'icon' => $modelClass::getDictionaryIcon(),
                'count' => $modelClass::count(),
            ];
        })->values();
    }

    /**
     * Получить класс модели по ключу
     *
     * @param string $key
     * @return string|null
     */
    public function getModelClass(string $key): ?string
    {
        return $this->dictionaryMap[$key] ?? null;
    }

    /**
     * Получить экземпляр модели по ключу
     *
     * @param string $key
     * @return Model|Dictionary|null
     */
    public function getModel(string $key): Model|Dictionary|null
    {
        $class = $this->getModelClass($key);

        if (!$class) {
            return null;
        }

        return new $class;
    }

    /**
     * Получить записи справочника с пагинацией
     *
     * @param string $key
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator|null
     */
    public function getItems(string $key, array $filters = [], int $perPage = 50): ?LengthAwarePaginator
    {
        $modelClass = $this->getModelClass($key);

        if (!$modelClass) {
            return null;
        }

        $query = $modelClass::query();

        // Добавляем связи для хостов
        if ($key === 'hosts') {
            $query->with(['store', 'lastAvailabilityLog']);
        }

        // Поиск
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Сортировка
        $sortField = $filters['sort_by'] ?? array_key_first($modelClass::getDefaultOrder());
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Получить конкретную запись справочника
     *
     * @param string $key
     * @param int $id
     * @return Model|Dictionary|null
     */
    public function getItem(string $key, int $id): Model|Dictionary|null
    {
        $modelClass = $this->getModelClass($key);

        if (!$modelClass) {
            return null;
        }

        return $modelClass::find($id);
    }

    /**
     * Создать запись в справочнике
     *
     * @param string $key
     * @param array $data
     * @return Model|Dictionary
     */
    public function createItem(string $key, array $data): Model|Dictionary
    {
        $modelClass = $this->getModelClass($key);

        if (!$modelClass) {
            throw new \InvalidArgumentException("Справочник {$key} не найден");
        }

        // Специальная обработка для User - хеширование пароля
        if ($key === 'users' && isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $modelClass::create($data);
    }

    /**
     * Обновить запись в справочнике
     *
     * @param string $key
     * @param int $id
     * @param array $data
     * @return Model|Dictionary
     */
    public function updateItem(string $key, int $id, array $data): Model|Dictionary
    {
        $item = $this->getItem($key, $id);

        if (!$item) {
            throw new \InvalidArgumentException("Запись #{$id} не найдена");
        }

        // Специальная обработка для User
        if ($key === 'users') {
            // Если пароль пустой - не обновляем его
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                // Хешируем новый пароль
                $data['password'] = Hash::make($data['password']);
            }
        }

        $item->update($data);

        return $item->fresh();
    }

    /**
     * Удалить запись из справочника
     *
     * @param string $key
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteItem(string $key, int $id): bool
    {
        $item = $this->getItem($key, $id);

        if (!$item) {
            throw new \InvalidArgumentException("Запись #{$id} не найдена");
        }

        if (!$item->canBeDeleted()) {
            $reason = $item->getDeleteRestrictionReason() ?? 'Запись используется в системе';
            throw new \RuntimeException($reason);
        }

        return $item->delete();
    }

    /**
     * Получить метаданные справочника
     *
     * @param string $key
     * @return array|null
     */
    public function getDictionaryMeta(string $key): ?array
    {
        $modelClass = $this->getModelClass($key);

        if (!$modelClass) {
            return null;
        }

        return [
            'key' => $key,
            'name' => $modelClass::getDictionaryName(),
            'singular_name' => $modelClass::getDictionarySingularName(),
            'description' => $modelClass::getDictionaryDescription(),
            'icon' => $modelClass::getDictionaryIcon(),
            'table_columns' => $modelClass::getTableColumns(),
            'form_fields' => $modelClass::getFormFields(),
            'create_validation_rules' => $modelClass::getCreateValidationRules(),
        ];
    }

    /**
     * Проверить существует ли справочник
     *
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        return isset($this->dictionaryMap[$key]);
    }

    /**
     * Получить данные для выпадающего списка
     *
     * @param string $key
     * @return array
     */
    public function forSelect(string $key): array
    {
        $modelClass = $this->getModelClass($key);

        if (!$modelClass) {
            return [];
        }

        return $modelClass::forSelect();
    }
}
