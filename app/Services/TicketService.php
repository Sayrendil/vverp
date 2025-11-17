<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Status;
use App\Models\TicketCategory;
use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Events\TicketAssigned;
use App\Enums\TicketStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TicketService
{
    /**
     * Получить список тикетов с фильтрацией и пагинацией
     */
    public function getTickets(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Ticket::with([
            'status',
            'problem',
            'store',
            'cash',
            'author',
            'executor',
            'ticketCategory',
            'ahoSpecialist'
        ]);

        // Фильтр по категории пользователя
        if ($user->ticket_category_id) {
            $query->where('ticket_category_id', $user->ticket_category_id);
        }

        // Фильтр по категории из запроса (для суперадминов)
        if (!$user->ticket_category_id && !empty($filters['category'])) {
            $query->where('ticket_category_id', $filters['category']);
        }

        // Фильтр поиска с защитой от SQL Injection wildcard символов
        if (!empty($filters['search'])) {
            // Экранируем wildcard символы % и _ для LIKE запроса
            $search = str_replace(['%', '_'], ['\%', '\_'], $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Фильтр по статусу
        if (!empty($filters['status'])) {
            $query->where('status_id', $filters['status']);
        }

        // Фильтр по проблеме
        if (!empty($filters['problem'])) {
            $query->where('problem_id', $filters['problem']);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    /**
     * Получить конкретный тикет
     */
    public function getTicket(int $ticketId): Ticket
    {
        return Ticket::with([
            'status',
            'problem',
            'store',
            'cash',
            'author',
            'executor',
            'ticketCategory',
            'ahoSpecialist',
            'attachments',
        ])->findOrFail($ticketId);
    }

    /**
     * Создать новый тикет
     */
    public function createTicket(User $author, array $data): Ticket
    {
        // Определяем категорию: приоритет данным из формы, затем из пользователя (если > 0)
        $categoryId = $data['ticket_category_id'] ?? ($author->ticket_category_id > 0 ? $author->ticket_category_id : null);

        // Получаем ID статуса "Создана" используя enum
        $createdStatusId = $data['status_id'] ?? TicketStatus::CREATED->value;

        return DB::transaction(function () use ($author, $data, $categoryId, $createdStatusId) {
            $ticket = Ticket::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status_id' => $createdStatusId,
                'problem_id' => $data['problem_id'],
                'store_id' => $data['store_id'] ?? null,
                'cash_id' => $data['cash_id'] ?? null,
                'author_id' => $author->id,
                'ticket_category_id' => $categoryId,
                'created_via' => $data['created_via'] ?? 'web',
            ]);

            $ticket->load([
                'status',
                'problem',
                'store',
                'cash',
                'author',
                'ticketCategory',
            ]);

            // Вызываем событие создания тикета
            event(new TicketCreated($ticket));

            return $ticket;
        });
    }

    /**
     * Обновить тикет
     */
    public function updateTicket(Ticket $ticket, array $data): Ticket
    {
        return DB::transaction(function () use ($ticket, $data) {
            $oldData = $ticket->toArray();

            $ticket->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status_id' => $data['status_id'],
                'problem_id' => $data['problem_id'],
                'store_id' => $data['store_id'] ?? null,
                'cash_id' => $data['cash_id'] ?? null,
                'executor_id' => $data['executor_id'] ?? null,
                'ticket_category_id' => $data['ticket_category_id'],
                'aho_specialist_id' => $data['aho_specialist_id'] ?? null,
            ]);

            // TODO: Логируем изменения
            // $this->logTicketChanges($ticket, $oldData, $ticket->toArray());

            // TODO: Отправляем уведомления об изменениях
            // $this->notificationService->notifyAboutTicketUpdate($ticket, $oldData);

            return $ticket->fresh([
                'status',
                'problem',
                'store',
                'cash',
                'executor',
                'ticketCategory',
                'ahoSpecialist',
            ]);
        });
    }

    /**
     * Обновить статус тикета
     */
    public function updateStatus(Ticket $ticket, int $statusId): Ticket
    {
        $oldStatus = $ticket->status;

        DB::transaction(function () use ($ticket, $statusId, $oldStatus) {
            $ticket->update(['status_id' => $statusId]);
            $ticket->load('status');

            // Вызываем событие изменения статуса
            if ($oldStatus->id !== $statusId) {
                event(new TicketStatusChanged($ticket, $oldStatus, $ticket->status));
            }
        });

        return $ticket->fresh(['status']);
    }

    /**
     * Назначить исполнителя на тикет
     */
    public function assignExecutor(Ticket $ticket, int $executorId): Ticket
    {
        DB::transaction(function () use ($ticket, $executorId) {
            $oldStatus = $ticket->status;
            $ticket->update(['executor_id' => $executorId]);
            $ticket->load('executor');

            // Вызываем событие назначения
            event(new TicketAssigned($ticket, $ticket->executor));

            // Если тикет был в статусе "Создана", меняем на "В работе"
            if ($oldStatus->id === TicketStatus::CREATED->value) {
                $ticket->update(['status_id' => TicketStatus::IN_PROGRESS->value]);
                $ticket->load('status');

                // Вызываем событие изменения статуса
                event(new TicketStatusChanged($ticket, $oldStatus, $ticket->status));
            }
        });

        return $ticket->fresh(['executor', 'status']);
    }

    /**
     * Назначить АХО специалиста на тикет
     */
    public function assignAhoSpecialist(Ticket $ticket, int $ahoSpecialistId): Ticket
    {
        DB::transaction(function () use ($ticket, $ahoSpecialistId) {
            $ticket->update(['aho_specialist_id' => $ahoSpecialistId]);

            // TODO: Отправляем уведомление в Telegram АХО специалисту
            // $this->notificationService->notifyAhoSpecialistViaTelegram($ticket);
        });

        return $ticket->fresh(['ahoSpecialist']);
    }

    /**
     * Удалить тикет
     */
    public function deleteTicket(Ticket $ticket): bool
    {
        return DB::transaction(function () use ($ticket) {
            // TODO: Логируем удаление
            // $this->logTicketDeletion($ticket);

            return $ticket->delete();
        });
    }

    /**
     * Проверить, может ли сотрудник редактировать свой тикет
     */
    public function canEmployeeEditTicket(User $user, Ticket $ticket): bool
    {
        // Сотрудник может редактировать только свои тикеты
        if ($user->id !== $ticket->author_id) {
            return false;
        }

        // Только если статус "Создана"
        if ($ticket->status_id !== TicketStatus::CREATED->value) {
            return false;
        }

        return true;
    }

    /**
     * Получить статистику по тикетам для пользователя
     */
    public function getStatistics(User $user): array
    {
        $query = Ticket::query();

        // Фильтр по категории пользователя
        if ($user->ticket_category_id) {
            $query->where('ticket_category_id', $user->ticket_category_id);
        }

        $total = $query->count();
        $byStatus = (clone $query)->select('status_id', DB::raw('count(*) as count'))
            ->groupBy('status_id')
            ->with('status')
            ->get()
            ->mapWithKeys(fn($item) => [$item->status->name => $item->count]);

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'created_today' => (clone $query)->whereDate('created_at', today())->count(),
            'in_progress' => (clone $query)->where('status_id', TicketStatus::IN_PROGRESS->value)->count(),
        ];
    }
}
