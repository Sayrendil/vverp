<?php

namespace App\DataTransferObjects\Telegram;

/**
 * DTO для пользователя Telegram
 */
readonly class User
{
    public function __construct(
        public int $id,
        public bool $isBot,
        public string $firstName,
        public ?string $lastName = null,
        public ?string $username = null,
        public ?string $languageCode = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            isBot: $data['is_bot'] ?? false,
            firstName: $data['first_name'],
            lastName: $data['last_name'] ?? null,
            username: $data['username'] ?? null,
            languageCode: $data['language_code'] ?? null,
        );
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . ($this->lastName ?? ''));
    }

    public function getMention(): string
    {
        return $this->username ? '@' . $this->username : $this->getFullName();
    }
}
