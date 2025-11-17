<?php

namespace App\Contracts;

/**
 * Интерфейс для справочников
 *
 * Все модели-справочники должны реализовывать этот интерфейс
 * для единообразного управления через админку
 */
interface Dictionary
{
    /**
     * Получить человекочитаемое название справочника (мн.ч.)
     *
     * @return string Например: "Магазины"
     */
    public static function getDictionaryName(): string;

    /**
     * Получить название справочника в единственном числе
     *
     * @return string Например: "Магазин"
     */
    public static function getDictionarySingularName(): string;

    /**
     * Получить описание справочника для UI
     *
     * @return string Например: "Список торговых точек"
     */
    public static function getDictionaryDescription(): string;

    /**
     * Получить ключ справочника для URL
     *
     * @return string Например: "stores"
     */
    public static function getDictionaryKey(): string;

    /**
     * Получить иконку для UI (emoji или класс иконки)
     *
     * @return string Например: "🏪"
     */
    public static function getDictionaryIcon(): string;

    /**
     * Получить правила валидации для создания записи
     *
     * @return array Laravel validation rules
     */
    public static function getCreateValidationRules(): array;

    /**
     * Получить правила валидации для обновления записи
     *
     * @param int $id ID записи для исключения из unique проверок
     * @return array Laravel validation rules
     */
    public static function getUpdateValidationRules(int $id): array;

    /**
     * Получить поля для отображения в таблице
     *
     * @return array Массив конфигураций полей
     */
    public static function getTableColumns(): array;

    /**
     * Получить поля для формы создания/редактирования
     *
     * @return array Массив конфигураций полей формы
     */
    public static function getFormFields(): array;

    /**
     * Проверить, может ли запись быть удалена
     * Обычно проверяется наличие связанных записей
     *
     * @return bool
     */
    public function canBeDeleted(): bool;

    /**
     * Получить причину, почему запись не может быть удалена
     *
     * @return string|null
     */
    public function getDeleteRestrictionReason(): ?string;

    /**
     * Получить данные для выпадающего списка
     *
     * @return array ['id' => 'label']
     */
    public static function forSelect(): array;

    /**
     * Получить порядок сортировки по умолчанию
     *
     * @return array ['column' => 'direction']
     */
    public static function getDefaultOrder(): array;
}
