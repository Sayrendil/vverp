<?php

namespace App\Models;

use App\Contracts\Dictionary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Host extends Model implements Dictionary
{
    protected $fillable = [
        'store_id',
        'name',
        'ip_address',
        'description',
        'is_active',
        'check_interval',
        'timeout',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'check_interval' => 'integer',
        'timeout' => 'integer',
    ];

    /**
     * Получить название справочника
     */
    public static function getDictionaryName(): string
    {
        return 'Хосты для мониторинга';
    }

    /**
     * Получить название справочника в единственном числе
     */
    public static function getDictionarySingularName(): string
    {
        return 'Хост';
    }

    /**
     * Получить описание справочника
     */
    public static function getDictionaryDescription(): string
    {
        return 'Список хостов (касс, серверов) для мониторинга доступности';
    }

    /**
     * Получить ключ справочника для URL
     */
    public static function getDictionaryKey(): string
    {
        return 'hosts';
    }

    /**
     * Получить иконку
     */
    public static function getDictionaryIcon(): string
    {
        return '🖥️';
    }

    /**
     * Правила валидации для создания
     */
    public static function getCreateValidationRules(): array
    {
        return [
            'store_id' => ['required', 'exists:stores,id'],
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'string', 'max:255'], // Поддерживаем IP и hostname
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'check_interval' => ['integer', 'min:1', 'max:1440'], // От 1 минуты до 24 часов
            'timeout' => ['integer', 'min:1', 'max:30'],
        ];
    }

    /**
     * Правила валидации для обновления
     */
    public static function getUpdateValidationRules(int $id): array
    {
        return static::getCreateValidationRules();
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
                'key' => 'store.name',
                'label' => 'Магазин',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'name',
                'label' => 'Название',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'ip_address',
                'label' => 'IP адрес',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'is_active',
                'label' => 'Активен',
                'sortable' => true,
                'type' => 'boolean',
            ],
            [
                'key' => 'last_check_status',
                'label' => 'Статус',
                'sortable' => false,
                'type' => 'status',
            ],
            [
                'key' => 'check_interval',
                'label' => 'Интервал (мин)',
                'sortable' => true,
                'width' => '120px',
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
                'key' => 'store_id',
                'label' => 'Магазин',
                'type' => 'select',
                'required' => true,
                'options' => Store::forSelect(),
            ],
            [
                'key' => 'name',
                'label' => 'Название',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Касса 1, Сервер магазина и т.д.',
            ],
            [
                'key' => 'ip_address',
                'label' => 'IP адрес',
                'type' => 'text',
                'required' => true,
                'placeholder' => '192.168.1.100',
                'help' => 'IP адрес или hostname для проверки',
            ],
            [
                'key' => 'description',
                'label' => 'Описание',
                'type' => 'textarea',
                'required' => false,
                'placeholder' => 'Дополнительная информация о хосте',
            ],
            [
                'key' => 'is_active',
                'label' => 'Активен мониторинг',
                'type' => 'checkbox',
                'required' => false,
                'default' => true,
            ],
            [
                'key' => 'check_interval',
                'label' => 'Интервал проверки (минуты)',
                'type' => 'number',
                'required' => false,
                'default' => 5,
                'help' => 'Как часто проверять доступность (минуты)',
            ],
            [
                'key' => 'timeout',
                'label' => 'Таймаут (секунды)',
                'type' => 'number',
                'required' => false,
                'default' => 3,
                'help' => 'Время ожидания ответа от хоста',
            ],
        ];
    }

    /**
     * Порядок сортировки по умолчанию
     */
    public static function getDefaultOrder(): array
    {
        return ['store_id' => 'asc', 'name' => 'asc'];
    }

    /**
     * Получить данные для select
     */
    public static function forSelect(): array
    {
        return static::query()
            ->with('store')
            ->orderBy('store_id', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->store->name . ' - ' . $item->name . ' (' . $item->ip_address . ')',
            ])
            ->values()
            ->toArray();
    }

    /**
     * Магазин к которому относится хост
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Логи доступности этого хоста
     */
    public function availabilityLogs(): HasMany
    {
        return $this->hasMany(HostAvailabilityLog::class);
    }

    /**
     * Последняя проверка доступности
     */
    public function lastAvailabilityLog()
    {
        return $this->hasOne(HostAvailabilityLog::class)->latestOfMany('checked_at');
    }

    /**
     * Защищенные связи для проверки при удалении
     */
    protected function getProtectedRelations(): array
    {
        return [
            'availabilityLogs' => 'Логи проверок',
        ];
    }

    /**
     * Scope для активных хостов
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для хостов конкретного магазина
     */
    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Scope для поиска
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('ip_address', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('store', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%");
                });
        });
    }

    /**
     * Проверка возможности удаления
     */
    public function canBeDeleted(): bool
    {
        // Можем удалять даже если есть логи (они каскадно удалятся)
        return true;
    }

    /**
     * Получить причину блокировки удаления
     */
    public function getDeleteRestrictionReason(): ?string
    {
        return null;
    }

    /**
     * Получить статус последней проверки
     */
    public function getLastCheckStatusAttribute(): ?string
    {
        $lastLog = $this->lastAvailabilityLog;

        if (!$lastLog) {
            return 'Не проверялся';
        }

        return $lastLog->is_available ? '✅ Доступен' : '❌ Недоступен';
    }

    /**
     * Получить статистику за период
     */
    public function getStatistics(int $days = 7): array
    {
        $logs = $this->availabilityLogs()
            ->where('checked_at', '>=', now()->subDays($days))
            ->get();

        $total = $logs->count();
        $available = $logs->where('is_available', true)->count();
        $unavailable = $total - $available;

        return [
            'total_checks' => $total,
            'available' => $available,
            'unavailable' => $unavailable,
            'uptime_percent' => $total > 0 ? round(($available / $total) * 100, 2) : 0,
            'avg_response_time' => $logs->where('is_available', true)->avg('response_time'),
        ];
    }
}
