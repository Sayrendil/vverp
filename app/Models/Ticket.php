<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status_id',
        'problem_id',
        'store_id',
        'cash_id',
        'author_id',
        'executor_id',
        'ticket_category_id',
        'aho_specialist_id',
        'created_via',
    ];

    /**
     * Получить статус тикета
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Получить проблему тикета
     */
    public function problem(): BelongsTo
    {
        return $this->belongsTo(Problem::class);
    }

    /**
     * Получить магазин тикета
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Получить кассу тикета
     */
    public function cash(): BelongsTo
    {
        return $this->belongsTo(Cash::class);
    }

    /**
     * Получить автора тикета
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Получить исполнителя тикета
     */
    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_id');
    }

    /**
     * Получить категорию тикета
     */
    public function ticketCategory(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class);
    }

    /**
     * Получить АХО специалиста тикета
     */
    public function ahoSpecialist(): BelongsTo
    {
        return $this->belongsTo(AhoSpecialist::class);
    }

    /**
     * Получить вложения тикета
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }
}
