<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Contracts\Dictionary;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements Dictionary
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'ticket_category_id',
        'telegram_id',
        'store_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'role_label',
        'is_admin',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Get role label for frontend
     */
    public function getRoleLabelAttribute(): string
    {
        return $this->role ? $this->role->label() : 'Не указана';
    }

    /**
     * Get is_admin flag for frontend
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Serialize for Inertia/JSON
     */
    public function toArray()
    {
        $array = parent::toArray();

        // Конвертируем enum в строку для frontend
        if (isset($array['role']) && $array['role'] instanceof UserRole) {
            $array['role'] = $array['role']->value;
        }

        return $array;
    }

    /**
     * Получить категорию тикетов пользователя
     */
    public function ticketCategory(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class);
    }

    /**
     * Магазин пользователя
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Заявки, созданные пользователем
     */
    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'author_id');
    }

    /**
     * Заявки, назначенные пользователю как исполнителю
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'executor_id');
    }

    /**
     * Проверить, является ли пользователь администратором
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Проверить, является ли пользователь сотрудником
     */
    public function isEmployee(): bool
    {
        return $this->role === UserRole::EMPLOYEE;
    }

    /**
     * Проверить, является ли пользователь АХО специалистом
     */
    public function isAhoSpecialist(): bool
    {
        return $this->role === UserRole::AHO_SPECIALIST;
    }

    /**
     * Может ли пользователь входить в систему
     */
    public function canLogin(): bool
    {
        return $this->role->canLogin();
    }

    // ==================== Dictionary Interface ====================

    /**
     * Получить название справочника
     */
    public static function getDictionaryName(): string
    {
        return 'Пользователи';
    }

    /**
     * Получить название справочника в единственном числе
     */
    public static function getDictionarySingularName(): string
    {
        return 'Пользователь';
    }

    /**
     * Получить описание справочника
     */
    public static function getDictionaryDescription(): string
    {
        return 'Управление пользователями системы';
    }

    /**
     * Получить ключ справочника для URL
     */
    public static function getDictionaryKey(): string
    {
        return 'users';
    }

    /**
     * Получить иконку
     */
    public static function getDictionaryIcon(): string
    {
        return '👤';
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
                'label' => 'Имя',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'email',
                'label' => 'Email',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'role_label',
                'label' => 'Роль',
                'sortable' => false,
            ],
            [
                'key' => 'created_at',
                'label' => 'Создан',
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
                'label' => 'Имя',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Введите имя',
            ],
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'required' => true,
                'placeholder' => 'user@example.com',
            ],
            [
                'key' => 'password',
                'label' => 'Пароль',
                'type' => 'password',
                'required' => false,
                'placeholder' => 'Оставьте пустым, чтобы не изменять',
                'help' => 'Минимум 8 символов. Оставьте пустым при редактировании, если не хотите менять пароль.',
            ],
            [
                'key' => 'role',
                'label' => 'Роль',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'admin', 'label' => 'Администратор'],
                    ['value' => 'employee', 'label' => 'Сотрудник'],
                    ['value' => 'aho_specialist', 'label' => 'Специалист АХО'],
                ],
            ],
            [
                'key' => 'store_id',
                'label' => 'Магазин',
                'type' => 'select',
                'required' => false,
                'options' => Store::forSelect(),
            ],
            [
                'key' => 'ticket_category_id',
                'label' => 'Категория заявок',
                'type' => 'select',
                'required' => false,
                'options' => TicketCategory::forSelect(),
            ],
        ];
    }

    /**
     * Правила валидации для создания
     */
    public static function getCreateValidationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:admin,employee,aho_specialist'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'ticket_category_id' => ['nullable', 'integer', 'exists:ticket_categories,id'],
        ];
    }

    /**
     * Правила валидации для обновления
     */
    public static function getUpdateValidationRules(int $id): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', "unique:users,email,{$id}"],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:admin,employee,aho_specialist'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'ticket_category_id' => ['nullable', 'integer', 'exists:ticket_categories,id'],
        ];
    }

    /**
     * Проверить, может ли запись быть удалена
     */
    public function canBeDeleted(): bool
    {
        // Нельзя удалить если есть созданные заявки
        if ($this->createdTickets()->exists()) {
            return false;
        }

        // Нельзя удалить если есть назначенные заявки
        if ($this->assignedTickets()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Получить причину, почему запись не может быть удалена
     */
    public function getDeleteRestrictionReason(): ?string
    {
        if ($this->createdTickets()->exists()) {
            $count = $this->createdTickets()->count();
            return "Нельзя удалить, есть созданные заявки: {$count}";
        }

        if ($this->assignedTickets()->exists()) {
            $count = $this->assignedTickets()->count();
            return "Нельзя удалить, есть назначенные заявки: {$count}";
        }

        return null;
    }

    /**
     * Получить данные для выпадающего списка
     */
    public static function forSelect(): array
    {
        return static::query()
            ->orderBy('name')
            ->get()
            ->map(fn($user) => [
                'value' => $user->id,
                'label' => $user->name,
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
     * Scope для поиска по имени и email
     */
    public function scopeSearch($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /**
     * Категории, в которых пользователь является исполнителем
     */
    public function executorCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            TicketCategory::class,
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
     * Проверить, является ли пользователь исполнителем в категории
     */
    public function isExecutorInCategory(int $categoryId): bool
    {
        return $this->executorCategories()
            ->where('ticket_category_id', $categoryId)
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Получить количество активных заявок как исполнитель
     */
    public function getActiveTicketsCountAttribute(): int
    {
        return $this->assignedTickets()->whereIn('status_id', [
            \App\Enums\TicketStatus::CREATED->value,
            \App\Enums\TicketStatus::IN_PROGRESS->value,
        ])->count();
    }
}
