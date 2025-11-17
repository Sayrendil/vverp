<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\Status;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие изменения статуса тикета
 *
 * Срабатывает когда статус тикета изменен
 */
class TicketStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public Status $oldStatus,
        public Status $newStatus
    ) {}
}
