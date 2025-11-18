<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\Enums\TicketStatus;
use Illuminate\Auth\Access\Response;

class TicketPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Все аутентифицированные пользователи могут просматривать список тикетов
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // Администратор видит все тикеты своей категории
        if ($user->isAdmin() && $user->ticket_category_id === $ticket->ticket_category_id) {
            return true;
        }

        // Автор тикета может просматривать свой тикет
        if ($user->id === $ticket->author_id) {
            return true;
        }

        // Исполнитель может просматривать назначенный ему тикет
        if ($user->id === $ticket->executor_id) {
            return true;
        }

        // Исполнитель категории может просматривать все тикеты категории
        // (чтобы иметь возможность взять их в работу)
        if ($ticket->ticket_category_id && $user->isExecutorInCategory($ticket->ticket_category_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Все аутентифицированные пользователи могут создавать тикеты
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        // Администратор может редактировать все тикеты своей категории
        if ($user->isAdmin() && $user->ticket_category_id === $ticket->ticket_category_id) {
            return true;
        }

        // Автор-сотрудник может редактировать ТОЛЬКО СВОЙ тикет и ТОЛЬКО в статусе "Создана"
        if ($user->isEmployee() && $user->id === $ticket->author_id) {
            return $ticket->status_id === TicketStatus::CREATED->value;
        }

        // Исполнитель может редактировать назначенный ему тикет
        if ($user->id === $ticket->executor_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        // Удалять может только администратор своей категории
        if ($user->isAdmin() && $user->ticket_category_id === $ticket->ticket_category_id) {
            return true;
        }

        // Автор-сотрудник может удалить свой тикет только если он еще не взят в работу
        if ($user->isEmployee() && $user->id === $ticket->author_id && !$ticket->executor_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Ticket $ticket): bool
    {
        // Восстанавливать может только администратор категории
        return $user->isAdmin() && $user->ticket_category_id === $ticket->ticket_category_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Ticket $ticket): bool
    {
        // Окончательное удаление только для администраторов категории
        return $user->isAdmin() && $user->ticket_category_id === $ticket->ticket_category_id;
    }

    /**
     * Determine whether the user can update the ticket status.
     */
    public function updateStatus(User $user, Ticket $ticket): bool
    {
        // Администратор может менять статус любых тикетов своей категории
        if ($user->isAdmin() && $user->ticket_category_id === $ticket->ticket_category_id) {
            return true;
        }

        // Исполнитель может менять статус назначенного ему тикета
        if ($user->id === $ticket->executor_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can assign a ticket to themselves or others.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        // Назначать тикеты могут только администраторы своей категории
        return $user->isAdmin() && $user->ticket_category_id === $ticket->ticket_category_id;
    }
}
