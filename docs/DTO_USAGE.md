# DTO (Data Transfer Objects) - Использование

## 📦 Что такое DTO?

DTO - типобезопасные объекты для передачи данных. Вместо массивов используем классы с четкими типами.

## ✅ Преимущества

```php
// ❌ БЕЗ DTO - работа с массивами
$update = ['update_id' => 123, 'message' => ['from' => ['id' => 456]]];
$userId = $update['message']['from']['id'];  // Легко ошибиться
$userName = $update['message']['from']['username'];  // может не существовать -> ошибка

// ✅ С DTO - типобезопасно
$update = Update::fromArray($telegramData);
$userId = $update->message->from->id;  // int (гарантировано)
$userName = $update->message->from->username;  // ?string (явно может быть null)

// IDE подсказывает доступные поля
$update->message->  // ← IDE показывает: messageId, date, chat, from, text...
```

---

## 🚀 Использование

### Базовый пример

```php
use App\DataTransferObjects\Telegram\Update;

// Получили данные от Telegram API
$rawUpdate = [
    'update_id' => 123456789,
    'message' => [
        'message_id' => 987,
        'from' => [
            'id' => 12345,
            'is_bot' => false,
            'first_name' => 'Иван',
            'username' => 'ivan123',
        ],
        'chat' => [
            'id' => 12345,
            'type' => 'private',
            'first_name' => 'Иван',
        ],
        'date' => 1699876543,
        'text' => '/start',
    ],
];

// Преобразуем в DTO
$update = Update::fromArray($rawUpdate);

// Используем типобезопасно
echo $update->updateId;                          // int: 123456789
echo $update->message->from->id;                 // int: 12345
echo $update->message->from->username;           // ?string: 'ivan123'
echo $update->message->text;                     // ?string: '/start'
echo $update->message->from->getFullName();      // string: 'Иван'
echo $update->message->from->getMention();       // string: '@ivan123'
```

### Проверки типов

```php
// Проверяем наличие данных
if ($update->hasMessage()) {
    $message = $update->message;

    if ($message->isCommand()) {
        $command = $message->getCommand();  // '/start'
    }

    if ($message->hasPhoto()) {
        $photos = $message->photo;
    }
}

if ($update->hasCallbackQuery()) {
    $callback = $update->callbackQuery;
    $data = $callback->parseData();  // ['type', 'id']
}
```

### В Handler'ах

```php
class CommandHandler implements UpdateHandler
{
    public function handle(array $updateArray): void
    {
        // Преобразуем массив в DTO
        $update = Update::fromArray($updateArray);

        // Теперь работаем типобезопасно
        $user = $update->getEffectiveUser();
        $chatId = $update->getEffectiveChatId();

        if (!$user || !$chatId) {
            return;
        }

        // IDE знает типы, автодополнение работает
        Log::info('Command from user', [
            'user_id' => $user->id,            // int
            'username' => $user->username,     // ?string
            'chat_id' => $chatId,              // int
        ]);
    }
}
```

### В сервисах

```php
class TelegramWizardService
{
    public function start(Update $update): void
    {
        $user = $update->getEffectiveUser();
        $chatId = $update->getEffectiveChatId();

        // Типы гарантированы, ошибок не будет
        $dbUser = User::where('telegram_id', $user->id)->first();

        if (!$dbUser) {
            $this->bot->sendMessage(
                $chatId,
                "Привет, {$user->getFullName()}! Вы не зарегистрированы."
            );
            return;
        }

        // ...
    }
}
```

---

## 📋 Доступные DTO классы

### Update

Главный объект обновления от Telegram.

```php
readonly class Update {
    public int $updateId;
    public ?Message $message;
    public ?CallbackQuery $callbackQuery;
    public ?Message $editedMessage;

    // Методы
    public function hasMessage(): bool;
    public function hasCallbackQuery(): bool;
    public function getEffectiveMessage(): ?Message;
    public function getEffectiveUser(): ?User;
    public function getEffectiveChatId(): ?int;
}
```

### Message

```php
readonly class Message {
    public int $messageId;
    public int $date;
    public Chat $chat;
    public ?User $from;
    public ?string $text;
    public ?array $photo;
    public ?array $document;
    public ?array $video;

    // Методы
    public function hasText(): bool;
    public function hasPhoto(): bool;
    public function hasMedia(): bool;
    public function isCommand(): bool;
    public function getCommand(): ?string;
}
```

### User

```php
readonly class User {
    public int $id;
    public bool $isBot;
    public string $firstName;
    public ?string $lastName;
    public ?string $username;
    public ?string $languageCode;

    // Методы
    public function getFullName(): string;
    public function getMention(): string;  // @username или имя
}
```

### Chat

```php
readonly class Chat {
    public int $id;
    public string $type;  // private, group, supergroup, channel
    public ?string $title;
    public ?string $username;

    // Методы
    public function isPrivate(): bool;
    public function isGroup(): bool;
}
```

### CallbackQuery

```php
readonly class CallbackQuery {
    public string $id;
    public User $from;
    public ?Message $message;
    public ?string $data;

    // Методы
    public function hasData(): bool;
    public function parseData(string $delimiter = '_'): array;
    public function getChatId(): ?int;
}
```

---

## 🎯 Практические примеры

### Обработка команды

```php
$update = Update::fromArray($rawData);

if ($update->hasMessage() && $update->message->isCommand()) {
    $command = $update->message->getCommand();
    $user = $update->message->from;
    $chatId = $update->message->chat->id;

    match($command) {
        '/start' => $this->handleStart($user, $chatId),
        '/help' => $this->handleHelp($chatId),
        default => $this->handleUnknown($chatId),
    };
}
```

### Обработка callback

```php
$update = Update::fromArray($rawData);

if ($update->hasCallbackQuery()) {
    $callback = $update->callbackQuery;
    [$type, $id] = $callback->parseData();

    $this->bot->answerCallbackQuery($callback->id);

    match($type) {
        'store' => $this->selectStore((int)$id, $callback->getChatId()),
        'category' => $this->selectCategory((int)$id, $callback->getChatId()),
        default => null,
    };
}
```

### Проверка медиа

```php
$update = Update::fromArray($rawData);

if ($update->hasMessage()) {
    $message = $update->message;

    if ($message->hasPhoto()) {
        $photos = $message->photo;
        $largestPhoto = end($photos);
        $fileId = $largestPhoto['file_id'];

        $this->handlePhoto($fileId, $message->chat->id);
    }

    if ($message->hasDocument()) {
        $document = $message->document;
        $this->handleDocument($document, $message->chat->id);
    }
}
```

---

## 💡 Расширение DTO

Можно добавлять свои методы:

```php
readonly class User
{
    // ... существующие поля

    // Дополнительные методы
    public function isAdmin(): bool
    {
        return in_array($this->id, config('telegram.admin_ids'));
    }

    public function getDisplayName(): string
    {
        return $this->username ?? $this->getFullName();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
        ];
    }
}
```

---

## 🔧 Интеграция с существующим кодом

### Постепенная миграция

```php
// Этап 1: Создаем DTO но продолжаем работать с массивами
public function handle(array $updateArray): void
{
    // Работаем с массивом
    $userId = $updateArray['message']['from']['id'];

    // Но также создаем DTO для новых частей
    $update = Update::fromArray($updateArray);
    $user = $update->getEffectiveUser();
}

// Этап 2: Постепенно переходим на DTO везде
public function handle(array $updateArray): void
{
    $update = Update::fromArray($updateArray);

    // Теперь только DTO
    $user = $update->getEffectiveUser();
    $chatId = $update->getEffectiveChatId();
}
```

---

## ✨ Best Practices

1. **Всегда используйте DTO для внешних данных**
   ```php
   // ✅ Хорошо
   $update = Update::fromArray($telegramData);

   // ❌ Плохо
   $userId = $telegramData['message']['from']['id'];
   ```

2. **Используйте null-safe операторы**
   ```php
   // ✅ Безопасно
   $username = $update->message?->from?->username;

   // ❌ Может упасть
   $username = $update->message->from->username;
   ```

3. **Проверяйте наличие данных**
   ```php
   // ✅ Правильно
   if ($update->hasMessage() && $update->message->hasText()) {
       $text = $update->message->text;
   }
   ```

4. **Используйте helper методы**
   ```php
   // ✅ Удобно
   $user = $update->getEffectiveUser();

   // ❌ Многословно
   $user = $update->message?->from ?? $update->callbackQuery?->from;
   ```

---

## 📚 Дополнительно

- Все DTO используют `readonly` классы (PHP 8.2+)
- Immutable - нельзя изменить после создания
- Type-safe - строгая типизация
- IDE-friendly - отличное автодополнение
