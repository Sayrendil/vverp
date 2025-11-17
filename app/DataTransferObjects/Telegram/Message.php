<?php

namespace App\DataTransferObjects\Telegram;

/**
 * DTO для сообщения Telegram
 */
readonly class Message
{
    public function __construct(
        public int $messageId,
        public int $date,
        public Chat $chat,
        public ?User $from = null,
        public ?string $text = null,
        public ?array $photo = null,
        public ?array $document = null,
        public ?array $video = null,
        public ?array $entities = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            messageId: $data['message_id'],
            date: $data['date'],
            chat: Chat::fromArray($data['chat']),
            from: isset($data['from']) ? User::fromArray($data['from']) : null,
            text: $data['text'] ?? null,
            photo: $data['photo'] ?? null,
            document: $data['document'] ?? null,
            video: $data['video'] ?? null,
            entities: $data['entities'] ?? null,
        );
    }

    public function hasText(): bool
    {
        return $this->text !== null;
    }

    public function hasPhoto(): bool
    {
        return $this->photo !== null;
    }

    public function hasDocument(): bool
    {
        return $this->document !== null;
    }

    public function hasVideo(): bool
    {
        return $this->video !== null;
    }

    public function hasMedia(): bool
    {
        return $this->hasPhoto() || $this->hasDocument() || $this->hasVideo();
    }

    public function isCommand(): bool
    {
        return $this->text && str_starts_with($this->text, '/');
    }

    public function getCommand(): ?string
    {
        if (!$this->isCommand()) {
            return null;
        }

        return explode(' ', $this->text)[0];
    }
}
