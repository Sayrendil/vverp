<?php

namespace App\Services;

use App\Models\User;
use App\Models\TicketCategory;
use App\Enums\TicketStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExecutorService
{
    /**
     * Получить все категории с их исполнителями
     */
    public function getCategoriesWithExecutors(): Collection
    {
        return TicketCategory::with([
            'executors' => function ($query) {
                $query->withCount([
                    'assignedTickets as active_tickets_count' => function ($q) {
                        $q->whereIn('status_id', [
                            TicketStatus::CREATED->value,
                            TicketStatus::IN_PROGRESS->value,
                        ]);
                    }
                ])->orderBy('name');
            }
        ])->orderBy('name')->get();
    }

    /**
     * Получить исполнителей конкретной категории
     */
    public function getExecutorsForCategory(int $categoryId): Collection
    {
        $category = TicketCategory::findOrFail($categoryId);

        return $category->executors()
            ->withCount([
                'assignedTickets as active_tickets_count' => function ($q) {
                    $q->whereIn('status_id', [
                        TicketStatus::CREATED->value,
                        TicketStatus::IN_PROGRESS->value,
                    ]);
                }
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Добавить исполнителя в категорию
     */
    public function addExecutorToCategory(
        int $userId,
        int $categoryId,
        array $options = []
    ): void {
        $user = User::findOrFail($userId);
        $category = TicketCategory::findOrFail($categoryId);

        // Проверяем, не добавлен ли уже
        if ($user->executorCategories()->where('ticket_category_id', $categoryId)->exists()) {
            throw new \Exception('Пользователь уже является исполнителем в этой категории');
        }

        $user->executorCategories()->attach($categoryId, [
            'is_active' => $options['is_active'] ?? true,
            'priority' => $options['priority'] ?? 0,
            'max_tickets' => $options['max_tickets'] ?? 10,
        ]);
    }

    /**
     * Удалить исполнителя из категории
     */
    public function removeExecutorFromCategory(int $userId, int $categoryId): void
    {
        $user = User::findOrFail($userId);
        $user->executorCategories()->detach($categoryId);
    }

    /**
     * Обновить настройки исполнителя в категории
     */
    public function updateExecutorSettings(
        int $userId,
        int $categoryId,
        array $settings
    ): void {
        $user = User::findOrFail($userId);

        $user->executorCategories()->updateExistingPivot($categoryId, [
            'is_active' => $settings['is_active'] ?? true,
            'priority' => $settings['priority'] ?? 0,
            'max_tickets' => $settings['max_tickets'] ?? 10,
        ]);
    }

    /**
     * Переключить активность исполнителя в категории
     */
    public function toggleExecutorStatus(int $userId, int $categoryId): bool
    {
        $user = User::findOrFail($userId);

        $executor = $user->executorCategories()
            ->where('ticket_category_id', $categoryId)
            ->first();

        if (!$executor) {
            throw new \Exception('Исполнитель не найден в этой категории');
        }

        $newStatus = !$executor->pivot->is_active;

        $user->executorCategories()->updateExistingPivot($categoryId, [
            'is_active' => $newStatus
        ]);

        return $newStatus;
    }

    /**
     * Получить доступных пользователей для добавления в категорию
     * (те, кто еще не исполнитель в этой категории)
     */
    public function getAvailableUsersForCategory(int $categoryId): Collection
    {
        return User::whereNotIn('id', function ($query) use ($categoryId) {
            $query->select('user_id')
                ->from('category_executors')
                ->where('ticket_category_id', $categoryId);
        })
        ->where('role', '!=', 'employee') // Только админы и АХО специалисты
        ->orderBy('name')
        ->get(['id', 'name', 'email', 'role']);
    }

    /**
     * Получить рекомендуемого исполнителя для категории
     * (с наименьшей нагрузкой и активного)
     */
    public function getRecommendedExecutor(int $categoryId): ?User
    {
        return User::whereHas('executorCategories', function ($query) use ($categoryId) {
            $query->where('ticket_category_id', $categoryId)
                  ->where('is_active', true);
        })
        ->withCount([
            'assignedTickets as active_tickets_count' => function ($q) {
                $q->whereIn('status_id', [
                    TicketStatus::CREATED->value,
                    TicketStatus::IN_PROGRESS->value,
                ]);
            }
        ])
        ->with(['executorCategories' => function ($query) use ($categoryId) {
            $query->where('ticket_category_id', $categoryId);
        }])
        ->get()
        ->filter(function ($user) {
            $pivot = $user->executorCategories->first()->pivot;
            // Фильтруем тех, кто не превысил лимит
            return $user->active_tickets_count < $pivot->max_tickets;
        })
        ->sortBy(function ($user) {
            $pivot = $user->executorCategories->first()->pivot;
            // Сортируем по приоритету (DESC) и нагрузке (ASC)
            return [-$pivot->priority, $user->active_tickets_count];
        })
        ->first();
    }

    /**
     * Получить статистику по исполнителям категории
     */
    public function getCategoryStatistics(int $categoryId): array
    {
        $category = TicketCategory::with(['executors'])->findOrFail($categoryId);

        $totalExecutors = $category->executors->count();
        $activeExecutors = $category->executors->where('pivot.is_active', true)->count();

        $totalActiveTickets = DB::table('category_executors')
            ->join('tickets', 'category_executors.user_id', '=', 'tickets.executor_id')
            ->where('category_executors.ticket_category_id', $categoryId)
            ->whereIn('tickets.status_id', [
                TicketStatus::CREATED->value,
                TicketStatus::IN_PROGRESS->value,
            ])
            ->count();

        return [
            'total_executors' => $totalExecutors,
            'active_executors' => $activeExecutors,
            'total_active_tickets' => $totalActiveTickets,
            'avg_tickets_per_executor' => $activeExecutors > 0
                ? round($totalActiveTickets / $activeExecutors, 1)
                : 0,
        ];
    }
}
