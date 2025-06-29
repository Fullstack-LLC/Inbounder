<?php

namespace Fullstack\Inbounder\Controllers;

use Fullstack\Inbounder\Services\InboundEmailMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MonitoringController extends Controller
{
    public function __construct(
        private InboundEmailMonitoringService $monitoringService
    ) {}

    /**
     * Health check endpoint.
     */
    public function healthCheck(): JsonResponse
    {
        $health = $this->monitoringService->getHealthCheck();

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;

        return response()->json($health, $statusCode);
    }

    /**
     * Get system alerts.
     */
    public function getAlerts(): JsonResponse
    {
        $alerts = $this->monitoringService->getAlerts();

        return response()->json([
            'alerts' => $alerts,
            'count' => count($alerts),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $startDate = \Carbon\Carbon::parse($request->input('start_date'));
        $endDate = \Carbon\Carbon::parse($request->input('end_date'));

        $metrics = $this->monitoringService->getPerformanceMetrics($startDate, $endDate);

        return response()->json($metrics);
    }

    /**
     * Get system status.
     */
    public function getSystemStatus(): JsonResponse
    {
        $health = $this->monitoringService->getHealthCheck();
        $alerts = $this->monitoringService->getAlerts();

        return response()->json([
            'status' => $health['status'],
            'alerts' => $alerts,
            'timestamp' => now()->toISOString(),
            'uptime' => $this->getUptime(),
        ]);
    }

    /**
     * Get uptime information.
     */
    private function getUptime(): array
    {
        // This would typically get actual uptime from your system
        // For now, return placeholder data
        return [
            'started_at' => now()->subDays(7)->toISOString(),
            'uptime_seconds' => 604800, // 7 days
            'uptime_formatted' => '7 days, 0 hours, 0 minutes',
        ];
    }
}
