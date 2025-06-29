<?php

namespace Fullstack\Inbounder\Controllers;

use Carbon\Carbon;
use Fullstack\Inbounder\Services\InboundEmailAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        private InboundEmailAnalyticsService $analyticsService
    ) {}

    /**
     * Get analytics for a date range.
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'tenant_id' => 'nullable|integer',
        ]);

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $tenantId = $request->input('tenant_id');

        $analytics = $this->analyticsService->getAnalytics($startDate, $endDate, $tenantId);

        return response()->json($analytics);
    }

    /**
     * Get real-time metrics.
     */
    public function getRealTimeMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'nullable|integer',
        ]);

        $tenantId = $request->input('tenant_id');
        $metrics = $this->analyticsService->getRealTimeMetrics($tenantId);

        return response()->json($metrics);
    }

    /**
     * Export analytics to CSV.
     */
    public function exportCsv(Request $request): Response
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'tenant_id' => 'nullable|integer',
        ]);

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $tenantId = $request->input('tenant_id');

        $csv = $this->analyticsService->exportToCsv($startDate, $endDate, $tenantId);

        $filename = "inbound-email-analytics-{$startDate->format('Y-m-d')}-to-{$endDate->format('Y-m-d')}.csv";

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Get top senders.
     */
    public function getTopSenders(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'limit' => 'nullable|integer|min:1|max:100',
            'tenant_id' => 'nullable|integer',
        ]);

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $limit = $request->input('limit', 10);
        $tenantId = $request->input('tenant_id');

        // This would be implemented in the analytics service
        $topSenders = []; // Placeholder

        return response()->json($topSenders);
    }

    /**
     * Get geographic distribution.
     */
    public function getGeographicDistribution(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'tenant_id' => 'nullable|integer',
        ]);

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $tenantId = $request->input('tenant_id');

        $analytics = $this->analyticsService->getAnalytics($startDate, $endDate, $tenantId);

        return response()->json($analytics['geography']);
    }

    /**
     * Get device distribution.
     */
    public function getDeviceDistribution(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'tenant_id' => 'nullable|integer',
        ]);

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $tenantId = $request->input('tenant_id');

        $analytics = $this->analyticsService->getAnalytics($startDate, $endDate, $tenantId);

        return response()->json($analytics['devices']);
    }
}
