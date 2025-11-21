<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostAvailabilityLog extends Model
{
    // Не используем timestamps, у нас есть checked_at
    public $timestamps = false;

    protected $fillable = [
        'host_id',
        'is_available',
        'response_time',
        'packet_loss',
        'error_message',
        'checked_at',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'response_time' => 'integer',
        'packet_loss' => 'integer',
        'checked_at' => 'datetime',
    ];

    /**
     * Хост к которому относится лог
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }

    /**
     * Получить цвет статуса
     */
    public function getStatusColorAttribute(): string
    {
        return $this->is_available ? 'green' : 'red';
    }

    /**
     * Получить текст статуса
     */
    public function getStatusTextAttribute(): string
    {
        return $this->is_available ? 'Доступен' : 'Недоступен';
    }

    /**
     * Получить форматированное время отклика
     */
    public function getFormattedResponseTimeAttribute(): string
    {
        if (!$this->response_time) {
            return 'N/A';
        }

        if ($this->response_time < 1000) {
            return $this->response_time . ' мс';
        }

        return round($this->response_time / 1000, 2) . ' сек';
    }
}
