<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AhoSpecialist extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'telegram_id',
        'telegram_username',
        'specialization',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Получить тикеты назначенные этому специалисту
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Получить активных специалистов
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Получить специалистов по специализации
     */
    public function scopeBySpecialization($query, string $specialization)
    {
        return $query->where('specialization', $specialization);
    }

    /**
     * Получить форматированный контакт
     */
    public function getContactAttribute(): string
    {
        $contact = $this->name;

        if ($this->phone) {
            $contact .= " | Телефон: {$this->phone}";
        }

        if ($this->telegram_username) {
            $contact .= " | Telegram: @{$this->telegram_username}";
        }

        return $contact;
    }
}
