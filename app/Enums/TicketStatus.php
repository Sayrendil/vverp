<?php

namespace App\Enums;

/**
 * Перечисление статусов тикетов
 *
 * Статические ID соответствуют записям в таблице statuses
 * из StatusSeeder
 */
enum TicketStatus: int
{
    case CREATED = 1;      // Создана
    case IN_PROGRESS = 2;  // В работе
    case CONFIRMED = 3;    // Подтверждена
    case POSTPONED = 4;    // Отложена
    case COMPLETED = 5;    // Завершена

    /**
     * Получить русское название статуса
     */
    public function label(): string
    {
        return match($this) {
            self::CREATED => 'Создана',
            self::IN_PROGRESS => 'В работе',
            self::CONFIRMED => 'Подтверждена',
            self::POSTPONED => 'Отложена',
            self::COMPLETED => 'Завершена',
        };
    }

    /**
     * Получить описание статуса
     */
    public function description(): string
    {
        return match($this) {
            self::CREATED => 'Заявка создана и ожидает назначения исполнителя',
            self::IN_PROGRESS => 'Заявка взята в работу',
            self::CONFIRMED => 'Заявка подтверждена и требует выполнения',
            self::POSTPONED => 'Заявка временно отложена',
            self::COMPLETED => 'Заявка выполнена и закрыта',
        };
    }

    /**
     * Проверить, можно ли редактировать тикет в этом статусе
     */
    public function isEditable(): bool
    {
        return $this === self::CREATED;
    }

    /**
     * Проверить, является ли статус финальным
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED]);
    }

    /**
     * Получить цвет для отображения в UI
     */
    public function color(): string
    {
        return match($this) {
            self::CREATED => 'blue',
            self::IN_PROGRESS => 'yellow',
            self::CONFIRMED => 'purple',
            self::POSTPONED => 'gray',
            self::COMPLETED => 'green',
        };
    }

    /**
     * Получить иконку для отображения в UI
     */
    public function icon(): string
    {
        return match($this) {
            self::CREATED => '📝',
            self::IN_PROGRESS => '⚙️',
            self::CONFIRMED => '✅',
            self::POSTPONED => '⏸️',
            self::COMPLETED => '🎉',
        };
    }

    /**
     * Получить все статусы для dropdown
     */
    public static function options(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'description' => $status->description(),
                'color' => $status->color(),
                'icon' => $status->icon(),
            ],
            self::cases()
        );
    }

    /**
     * Получить статус по ID (безопасно)
     */
    public static function tryFromId(?int $id): ?self
    {
        if ($id === null) {
            return null;
        }

        return self::tryFrom($id);
    }
}
