<?php

namespace App\Http\Controllers;

use App\Models\Host;
use App\Services\MonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // Rate limiting: максимум 3 проверки в минуту для одного хоста
        $key = 'check-host-' . $hostId;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Слишком много запросов. Попробуйте через {$seconds} сек.",
            ], 429);
        }

        RateLimiter::hit($key, 60); // 60 секунд

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
        // Rate limiting: максимум 2 проверки всех хостов магазина в 5 минут
        $key = 'check-store-' . $storeId;

        if (RateLimiter::tooManyAttempts($key, 2)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Слишком много запросов. Попробуйте через " . ceil($seconds / 60) . " мин.",
            ], 429);
        }

        RateLimiter::hit($key, 300); // 5 минут

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
        // Rate limiting: максимум 1 проверка всех хостов в 10 минут
        $key = 'check-all-hosts';

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Слишком много запросов. Попробуйте через " . ceil($seconds / 60) . " мин.",
            ], 429);
        }

        RateLimiter::hit($key, 600); // 10 минут

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

    /**
     * Healthcheck endpoint для мониторинга системы мониторинга
     * Проверяет работоспособность: БД, очередей, последних проверок
     */
    public function healthcheck(): JsonResponse
    {
        $status = 'healthy';
        $issues = [];

        try {
            // 1. Проверка доступности БД
            $dbConnected = true;
            try {
                \DB::connection()->getPdo();
            } catch (\Exception $e) {
                $dbConnected = false;
                $issues[] = 'Database connection failed';
                $status = 'unhealthy';
            }

            // 2. Проверка активности проверок (последняя проверка должна быть не старше 10 минут)
            $lastCheck = HostAvailabilityLog::latest('checked_at')->first();
            $lastCheckTime = $lastCheck ? $lastCheck->checked_at : null;
            $minutesSinceLastCheck = $lastCheckTime ? now()->diffInMinutes($lastCheckTime) : null;

            if (!$lastCheckTime || $minutesSinceLastCheck > 10) {
                $issues[] = 'No recent checks (last check: ' . ($minutesSinceLastCheck ?? 'never') . ' minutes ago)';
                $status = 'warning';
            }

            // 3. Проверка наличия активных хостов
            $activeHostsCount = Host::active()->count();
            if ($activeHostsCount === 0) {
                $issues[] = 'No active hosts configured';
                $status = 'warning';
            }

            // 4. Проверка количества проблемных хостов
            $problematicHostsCount = $this->monitoringService->getProblematicHosts(10)->count();
            if ($problematicHostsCount > 0) {
                $issues[] = "{$problematicHostsCount} problematic hosts detected";
                // Не меняем статус на unhealthy, это нормальная ситуация
            }

            return response()->json([
                'status' => $status,
                'timestamp' => now()->toIso8601String(),
                'checks' => [
                    'database' => $dbConnected ? 'ok' : 'failed',
                    'last_check_minutes_ago' => $minutesSinceLastCheck,
                    'active_hosts' => $activeHostsCount,
                    'problematic_hosts' => $problematicHostsCount,
                ],
                'issues' => $issues,
            ], $status === 'unhealthy' ? 503 : 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'timestamp' => now()->toIso8601String(),
                'error' => $e->getMessage(),
            ], 503);
        }
    }
}
