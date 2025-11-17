# 📋 Логика подтверждения заявок автором

## 🎯 Бизнес-процесс

```
СОЗДАНА (1)
    ↓ [Исполнитель: "Взять в работу"]
В РАБОТЕ (2) + confirmed_by_author_at = NULL
    ↓ [Исполнитель: "На подтверждение"]
В РАБОТЕ (2) + confirmed_by_author_at = NULL (ожидает подтверждения)
    ↓ [Автор: "Подтвердить"]
ПОДТВЕРЖДЕНА (3) + confirmed_by_author_at = timestamp
    ↓ [Автоматически или вручную]
ЗАВЕРШЕНА (5)
```

## 🔑 Ключевое поле: `confirmed_by_author_at`

### Значения:

- **`NULL`** = Автор еще не подтверждал
- **`timestamp`** = Дата и время когда автор подтвердил

### Состояния заявки:

| status_id | confirmed_by_author_at | Состояние | Описание |
|-----------|------------------------|-----------|----------|
| 2 (В работе) | NULL | Просто в работе | Исполнитель работает над заявкой |
| 2 (В работе) | NULL | **На подтверждении** | Исполнитель отправил на проверку автору |
| 3 (Подтверждена) | timestamp | Подтверждена | Автор подтвердил выполнение |
| 5 (Завершена) | timestamp | Завершена | Заявка закрыта |

## 🤔 Как различать "В работе" и "На подтверждении"?

### В коде используется логика:

```php
// Проверка: ожидает ли подтверждения?
public function isAwaitingConfirmation(): bool
{
    return $this->status_id === TicketStatus::IN_PROGRESS->value
        && is_null($this->confirmed_by_author_at);
}
```

**НО!** Эта проверка срабатывает и для обычного состояния "В работе".

### ✅ Правильная логика (нужно добавить дополнительный признак):

**Вариант А: Добавить поле `sent_for_confirmation_at`**

```php
// Когда исполнитель отправляет на подтверждение
$ticket->update([
    'sent_for_confirmation_at' => now()
]);

// Проверка
public function isAwaitingConfirmation(): bool
{
    return $this->status_id === TicketStatus::IN_PROGRESS->value
        && !is_null($this->sent_for_confirmation_at)
        && is_null($this->confirmed_by_author_at);
}
```

**Вариант Б: Использовать связку с executor_id**

```php
// "На подтверждении" = есть исполнитель + статус IN_PROGRESS
public function isAwaitingConfirmation(): bool
{
    return $this->status_id === TicketStatus::IN_PROGRESS->value
        && !is_null($this->executor_id)
        && is_null($this->confirmed_by_author_at)
        && $this->hasBeenSentForConfirmation(); // проверка через activity log
}
```

## 🚀 Текущая реализация (с недостатком)

### Проблема:
В текущей версии метод `isAwaitingConfirmation()` **не может отличить**:
- Заявку которую только что взяли в работу
- Заявку которую отправили на подтверждение

Обе имеют: `status_id = 2` и `confirmed_by_author_at = NULL`

### Решение:
Добавить поле `sent_for_confirmation_at` для явного признака.

## 📊 Workflow методов

### 1. takeToWork() - Взять в работу
```php
$ticket->update([
    'executor_id' => $executor->id,
    'status_id' => TicketStatus::IN_PROGRESS->value,
    'confirmed_by_author_at' => null,  // Сбрасываем
]);
```

### 2. sendForConfirmation() - Отправить на подтверждение
```php
// Текущая версия: ничего не меняет!
// confirmed_by_author_at остается NULL

// Нужно добавить:
$ticket->update([
    'sent_for_confirmation_at' => now()
]);
```

### 3. confirmCompletion() - Подтвердить выполнение
```php
$ticket->update([
    'confirmed_by_author_at' => now(),
    'status_id' => TicketStatus::CONFIRMED->value
]);
```

### 4. rejectCompletion() - Вернуть в работу
```php
// Текущая версия: ничего не меняет

// Нужно добавить:
$ticket->update([
    'sent_for_confirmation_at' => null  // Сбрасываем признак
]);
```

## ✅ Рекомендуемые изменения

### Добавить миграцию:
```php
Schema::table('tickets', function (Blueprint $table) {
    $table->timestamp('sent_for_confirmation_at')
        ->nullable()
        ->after('confirmed_by_author_at')
        ->comment('Дата отправки на подтверждение автору');
});
```

### Обновить модель:
```php
protected $fillable = [
    // ...
    'confirmed_by_author_at',
    'sent_for_confirmation_at',
];

protected $casts = [
    'confirmed_by_author_at' => 'datetime',
    'sent_for_confirmation_at' => 'datetime',
];

public function isAwaitingConfirmation(): bool
{
    return $this->status_id === \App\Enums\TicketStatus::IN_PROGRESS->value
        && !is_null($this->sent_for_confirmation_at)
        && is_null($this->confirmed_by_author_at);
}
```

### Обновить TicketWorkflowService:

```php
// В sendForConfirmation()
$ticket->update([
    'sent_for_confirmation_at' => now()
]);

// В confirmCompletion()
$ticket->update([
    'confirmed_by_author_at' => now(),
    'sent_for_confirmation_at' => null,  // Очищаем
    'status_id' => TicketStatus::CONFIRMED->value
]);

// В rejectCompletion()
$ticket->update([
    'sent_for_confirmation_at' => null  // Сбрасываем признак
]);
```

## 📈 Преимущества такого подхода

1. ✅ **Явное состояние** "на подтверждении"
2. ✅ **История**: видно когда отправили на проверку
3. ✅ **Метрики**: можно вычислять время ожидания подтверждения
4. ✅ **Фильтрация**: легко найти все заявки на подтверждении

```sql
-- Заявки на подтверждении
SELECT * FROM tickets
WHERE status_id = 2
  AND sent_for_confirmation_at IS NOT NULL
  AND confirmed_by_author_at IS NULL;

-- Среднее время подтверждения
SELECT AVG(TIMESTAMPDIFF(SECOND, sent_for_confirmation_at, confirmed_by_author_at))
FROM tickets
WHERE confirmed_by_author_at IS NOT NULL;
```

## 🎯 Текущая ситуация

**Что работает:**
- ✅ Автор может подтверждать через Telegram
- ✅ Время подтверждения сохраняется
- ✅ Статус меняется на CONFIRMED

**Что нужно доработать:**
- ❌ Добавить поле `sent_for_confirmation_at`
- ❌ Обновить логику `sendForConfirmation()`
- ❌ Обновить логику `rejectCompletion()`
- ❌ Исправить метод `isAwaitingConfirmation()`

---

**Создано**: 17.11.2024
**Версия**: 1.0 (с указанием недостатка)
