<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dictionary\StoreDictionaryRequest;
use App\Http\Requests\Dictionary\UpdateDictionaryRequest;
use App\Services\DictionaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Контроллер для управления справочниками
 *
 * Универсальный CRUD для всех справочников системы
 */
class DictionaryController extends Controller
{
    public function __construct(
        protected DictionaryService $dictionaryService
    ) {}

    /**
     * Список всех справочников
     *
     * GET /dictionaries
     */
    public function index(): Response
    {
        $dictionaries = $this->dictionaryService->getAllDictionaries();

        return Inertia::render('Dictionaries/Index', [
            'dictionaries' => $dictionaries,
        ]);
    }

    /**
     * Управление конкретным справочником
     *
     * GET /dictionaries/{dictionary}
     */
    public function show(Request $request, string $dictionary): Response
    {
        // Проверяем существование справочника
        if (!$this->dictionaryService->exists($dictionary)) {
            abort(404, 'Справочник не найден');
        }

        // Получаем метаданные
        $meta = $this->dictionaryService->getDictionaryMeta($dictionary);

        // Получаем записи с фильтрацией и пагинацией
        $items = $this->dictionaryService->getItems(
            $dictionary,
            [
                'search' => $request->input('search'),
                'sort_by' => $request->input('sort_by'),
                'sort_direction' => $request->input('sort_direction', 'asc'),
            ],
            perPage: (int) $request->input('per_page', 50)
        );

        return Inertia::render('Dictionaries/Show', [
            'dictionary' => $meta,
            'items' => $items,
            'filters' => [
                'search' => $request->input('search'),
                'sort_by' => $request->input('sort_by'),
                'sort_direction' => $request->input('sort_direction', 'asc'),
            ],
        ]);
    }

    /**
     * Создание записи в справочнике
     *
     * POST /dictionaries/{dictionary}
     */
    public function store(StoreDictionaryRequest $request, string $dictionary): RedirectResponse
    {
        if (!$this->dictionaryService->exists($dictionary)) {
            abort(404, 'Справочник не найден');
        }

        try {
            $item = $this->dictionaryService->createItem(
                $dictionary,
                $request->validated()
            );

            $meta = $this->dictionaryService->getDictionaryMeta($dictionary);

            return redirect()
                ->back()
                ->with('success', "{$meta['singular_name']} успешно создан");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Ошибка при создании: ' . $e->getMessage()]);
        }
    }

    /**
     * Обновление записи в справочнике
     *
     * PUT /dictionaries/{dictionary}/{id}
     */
    public function update(
        UpdateDictionaryRequest $request,
        string $dictionary,
        int $id
    ): RedirectResponse {
        if (!$this->dictionaryService->exists($dictionary)) {
            abort(404, 'Справочник не найден');
        }

        try {
            $item = $this->dictionaryService->updateItem(
                $dictionary,
                $id,
                $request->validated()
            );

            $meta = $this->dictionaryService->getDictionaryMeta($dictionary);

            return redirect()
                ->back()
                ->with('success', "{$meta['singular_name']} успешно обновлен");

        } catch (\InvalidArgumentException $e) {
            abort(404, $e->getMessage());
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Ошибка при обновлении: ' . $e->getMessage()]);
        }
    }

    /**
     * Удаление записи из справочника
     *
     * DELETE /dictionaries/{dictionary}/{id}
     */
    public function destroy(string $dictionary, int $id): RedirectResponse
    {
        if (!$this->dictionaryService->exists($dictionary)) {
            abort(404, 'Справочник не найден');
        }

        try {
            $this->dictionaryService->deleteItem($dictionary, $id);

            $meta = $this->dictionaryService->getDictionaryMeta($dictionary);

            return redirect()
                ->back()
                ->with('success', "{$meta['singular_name']} успешно удален");

        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withErrors(['delete' => $e->getMessage()]);

        } catch (\InvalidArgumentException $e) {
            abort(404, $e->getMessage());

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['delete' => 'Ошибка при удалении: ' . $e->getMessage()]);
        }
    }

    /**
     * API: Получить данные для выпадающего списка
     *
     * GET /api/dictionaries/{dictionary}/select
     */
    public function select(string $dictionary): array
    {
        if (!$this->dictionaryService->exists($dictionary)) {
            abort(404, 'Справочник не найден');
        }

        return $this->dictionaryService->forSelect($dictionary);
    }
}
