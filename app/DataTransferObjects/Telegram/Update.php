<?php

namespace App\DataTransferObjects\Telegram;

/**
 * DTO для обновления от Telegram
 */
readonly class Update
{
    public function __construct(
        public int $updateId,
        public ?Message $message = null,
        public ?CallbackQuery $callbackQuery = null,
        public ?Message $editedMessage = null,
        public ?Message $channelPost = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            updateId: $data['update_id'],
            message: isset($data['message']) ? Message::fromArray($data['message']) : null,
            callbackQuery: isset($data['callback_query']) ? CallbackQuery::fromArray($data['callback_query']) : null,
            editedMessage: isset($data['edited_message']) ? Message::fromArray($data['edited_message']) : null,
            channelPost: isset($data['channel_post']) ? Message::fromArray($data['channel_post']) : null,
        );
    }

    public function hasMessage(): bool
    {
        return $this->message !== null;
    }

    public function hasCallbackQuery(): bool
    {
        return $this->callbackQuery !== null;
    }

    public function hasEditedMessage(): bool
    {
        return $this->editedMessage !== null;
    }

    public function getEffectiveMessage(): ?Message
    {
        return $this->message ?? $this->editedMessage ?? $this->channelPost;
    }

    public function getEffectiveUser(): ?User
    {
        if ($this->callbackQuery) {
            return $this->callbackQuery->from;
        }

        return $this->getEffectiveMessage()?->from;
    }

    public function getEffectiveChatId(): ?int
    {
        if ($this->callbackQuery) {
            return $this->callbackQuery->getChatId();
        }

        return $this->getEffectiveMessage()?->chat->id;
    }
}
