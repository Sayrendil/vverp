<?php

namespace App\DataTransferObjects\Telegram;

/**
 * DTO для callback query (нажатие на inline кнопку)
 */
readonly class CallbackQuery
{
    public function __construct(
        public string $id,
        public User $from,
        public ?Message $message = null,
        public ?string $inlineMessageId = null,
        public ?string $data = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            from: User::fromArray($data['from']),
            message: isset($data['message']) ? Message::fromArray($data['message']) : null,
            inlineMessageId: $data['inline_message_id'] ?? null,
            data: $data['data'] ?? null,
        );
    }

    public function hasData(): bool
    {
        return $this->data !== null;
    }

    public function parseData(string $delimiter = '_'): array
    {
        if (!$this->data) {
            return [];
        }

        return explode($delimiter, $this->data);
    }

    public function getChatId(): ?int
    {
        return $this->message?->chat->id;
    }
}
