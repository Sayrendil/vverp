<?php

namespace App\Http\Controllers;

use App\Models\Host;
use App\Services\MonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Контроллер для системы мониторинга
 */
class MonitoringController extends Controller
{
    public function __construct(
        protected MonitoringService $monitoringService
    ) {}

    /**
     * Дашборд мониторинга
     */
    public function index(Request $request): Response
    {
        $days = (int)($request->get('days', 7));

        $statistics = $this->monitoringService->getOverallStatistics($days);
        $problematicHosts = $this->monitoringService->getProblematicHosts(10);

        return Inertia::render('Monitoring/Dashboard', [
            'statistics' => $statistics,
            'problematicHosts' => $problematicHosts,
            'days' => $days,
        ]);
    }

    /**
     * Детальная статистика по хосту
     */
    public function show(Request $request, int $hostId): Response
    {
        $days = (int)($request->get('days', 7));

        $statistics = $this->monitoringService->getHostStatistics($hostId, $days);

        return Inertia::render('Monitoring/HostDetails', [
            'statistics' => $statistics,
            'days' => $days,
        ]);
    }

    /**
     * Ручная проверка хоста
     */
    public function checkHost(int $hostId): JsonResponse
    {
        try {
            $host = Host::findOrFail($hostId);

            $this->monitoringService->checkHost($hostId, useQueue: true);

            return response()->json([
                'success' => true,
                'message' => "Проверка хоста {$host->name} добавлена в очередь",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Проверить все хосты магазина
     */
    public function checkStoreHosts(int $storeId): JsonResponse
    {
        try {
            $count = $this->monitoringService->checkStoreHosts($storeId, useQueue: true);

            return response()->json([
                'success' => true,
                'message' => "Запущено проверок: {$count}",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Проверить все активные хосты
     */
    public function checkAllHosts(): JsonResponse
    {
        try {
            $count = $this->monitoringService->checkAllActiveHosts(useQueue: true);

            return response()->json([
                'success' => true,
                'message' => "Запущено проверок: {$count}",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить статистику в формате JSON (для обновления)
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $days = (int)($request->get('days', 7));

        $statistics = $this->monitoringService->getOverallStatistics($days);
        $problematicHosts = $this->monitoringService->getProblematicHosts(10);

        return response()->json([
            'statistics' => $statistics,
            'problematicHosts' => $problematicHosts,
        ]);
    }

    /**
     * Получить статистику по хосту в формате JSON
     */
    public function getHostStatistics(Request $request, int $hostId): JsonResponse
    {
        $days = (int)($request->get('days', 7));

        $statistics = $this->monitoringService->getHostStatistics($hostId, $days);

        return response()->json($statistics);
    }
}
