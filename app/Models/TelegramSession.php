<?php

namespace App\Models;

use App\Enums\TelegramStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramSession extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'user_id',
        'step',
        'data',
        'message_id',
        'expires_at',
    ];

    protected $casts = [
        'step' => TelegramStep::class,
        'data' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getData(string $key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    public function setData(string $key, $value): void
    {
        // Получаем свежие данные из БД для избежания race condition
        $data = $this->fresh()->data ?? [];
        data_set($data, $key, $value);
        $this->update(['data' => $data]);
    }

    public function clearData(): void
    {
        $this->update([
            'data' => null,
            'step' => TelegramStep::IDLE,
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function extend(): void
    {
        $this->update(['expires_at' => now()->addMinutes(30)]);
    }

    /**
     * Установить ID сообщения для wizard'а
     */
    public function setMessageId(?int $messageId): void
    {
        $this->update(['message_id' => $messageId]);
    }

    /**
     * Получить ID сообщения wizard'а
     */
    public function getMessageId(): ?int
    {
        return $this->message_id;
    }

    /**
     * Есть ли активное сообщение wizard'а
     */
    public function hasWizardMessage(): bool
    {
        return $this->message_id !== null;
    }
}
