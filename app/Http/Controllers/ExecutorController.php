<?php

namespace App\Http\Controllers;

use App\Services\ExecutorService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExecutorController extends Controller
{
    public function __construct(
        private ExecutorService $executorService
    ) {
        // Проверка прав доступа осуществляется через middleware в роутах
    }

    /**
     * Показать страницу управления исполнителями
     *
     * GET /executors
     */
    public function index(): Response
    {
        $categories = $this->executorService->getCategoriesWithExecutors();

        return Inertia::render('Executors/Index', [
            'categories' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'executors' => $category->executors->map(function ($executor) {
                        return [
                            'id' => $executor->id,
                            'name' => $executor->name,
                            'email' => $executor->email,
                            'role' => $executor->role->value,
                            'role_label' => $executor->role_label,
                            'is_active' => (bool) $executor->pivot->is_active,
                            'priority' => (int) $executor->pivot->priority,
                            'max_tickets' => (int) $executor->pivot->max_tickets,
                            'active_tickets_count' => $executor->active_tickets_count ?? 0,
                        ];
                    }),
                    'statistics' => $this->executorService->getCategoryStatistics($category->id),
                ];
            }),
        ]);
    }

    /**
     * Показать доступных пользователей для добавления
     *
     * GET /executors/available/{categoryId}
     */
    public function available(int $categoryId)
    {
        $users = $this->executorService->getAvailableUsersForCategory($categoryId);

        return response()->json([
            'users' => $users->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ])
        ]);
    }

    /**
     * Добавить исполнителя в категорию
     *
     * POST /executors
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:ticket_categories,id',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0|max:100',
            'max_tickets' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $this->executorService->addExecutorToCategory(
                $validated['user_id'],
                $validated['category_id'],
                [
                    'is_active' => $validated['is_active'] ?? true,
                    'priority' => $validated['priority'] ?? 0,
                    'max_tickets' => $validated['max_tickets'] ?? 10,
                ]
            );

            return redirect()->back()->with('success', 'Исполнитель добавлен в категорию');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Обновить настройки исполнителя
     *
     * PUT /executors/{userId}/{categoryId}
     */
    public function update(Request $request, int $userId, int $categoryId): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
            'priority' => 'required|integer|min:0|max:100',
            'max_tickets' => 'required|integer|min:1|max:100',
        ]);

        try {
            $this->executorService->updateExecutorSettings(
                $userId,
                $categoryId,
                $validated
            );

            return redirect()->back()->with('success', 'Настройки исполнителя обновлены');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Переключить активность исполнителя
     *
     * POST /executors/{userId}/{categoryId}/toggle
     */
    public function toggle(int $userId, int $categoryId): RedirectResponse
    {
        try {
            $newStatus = $this->executorService->toggleExecutorStatus($userId, $categoryId);

            $message = $newStatus
                ? 'Исполнитель активирован'
                : 'Исполнитель деактивирован';

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Удалить исполнителя из категории
     *
     * DELETE /executors/{userId}/{categoryId}
     */
    public function destroy(int $userId, int $categoryId): RedirectResponse
    {
        try {
            $this->executorService->removeExecutorFromCategory($userId, $categoryId);

            return redirect()->back()->with('success', 'Исполнитель удален из категории');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
