<?php

namespace Fullstack\Inbounder\Services;

use Carbon\Carbon;
use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Models\InboundEmailEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InboundEmailMonitoringService
{
    /**
     * Log an operation with structured data.
     */
    public function logOperation(string $operation, array $data, string $level = 'info'): void
    {
        $logData = [
            'operation' => $operation,
            'timestamp' => Carbon::now()->toISOString(),
            'data' => $data,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];

        Log::channel('inbounder')->$level('Inbounder Operation', $logData);
    }

    /**
     * Track performance metrics.
     */
    public function trackPerformance(string $operation, callable $callback)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            $result = $callback();

            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $this->recordPerformanceMetrics($operation, $endTime - $startTime, $endMemory - $startMemory);

            return $result;
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $this->recordPerformanceMetrics($operation, $endTime - $startTime, $endMemory - $startMemory, $e);
            throw $e;
        }
    }

    /**
     * Record performance metrics.
     */
    private function recordPerformanceMetrics(string $operation, float $duration, int $memoryUsage, ?\Exception $exception = null): void
    {
        $metrics = [
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage_bytes' => $memoryUsage,
            'timestamp' => Carbon::now()->toISOString(),
            'success' => $exception === null,
        ];

        if ($exception) {
            $metrics['error'] = $exception->getMessage();
            $metrics['error_code'] = $exception->getCode();
        }

        // Store in cache for real-time monitoring
        $key = "inbounder:performance:{$operation}:" . Carbon::now()->format('Y-m-d-H');
        Cache::put($key, $metrics, 3600);

        // Log performance data
        Log::channel('inbounder')->info('Performance Metric', $metrics);
    }

    /**
     * Get health check data.
     */
    public function getHealthCheck(): array
    {
        $now = Carbon::now();
        $lastHour = $now->copy()->subHour();

        try {
            // Check database connectivity
            DB::connection()->getPdo();
            $dbStatus = 'healthy';
        } catch (\Exception $e) {
            $dbStatus = 'unhealthy';
        }

        // Check recent email processing
        $recentEmails = InboundEmail::where('created_at', '>=', $lastHour)->count();
        $recentEvents = InboundEmailEvent::where('occurred_at', '>=', $lastHour)->count();

        // Check for errors in the last hour
        $errorCount = $this->getErrorCount($lastHour);

        return [
            'status' => $dbStatus === 'healthy' && $errorCount < 10 ? 'healthy' : 'degraded',
            'timestamp' => $now->toISOString(),
            'database' => [
                'status' => $dbStatus,
                'connection' => config('database.default'),
            ],
            'performance' => [
                'emails_last_hour' => $recentEmails,
                'events_last_hour' => $recentEvents,
                'errors_last_hour' => $errorCount,
                'average_processing_time_ms' => $this->getAverageProcessingTime(),
            ],
            'storage' => [
                'disk_usage' => $this->getStorageUsage(),
                'attachment_count' => $this->getAttachmentCount(),
            ],
            'webhooks' => [
                'last_webhook_received' => $this->getLastWebhookTime(),
                'webhook_success_rate' => $this->getWebhookSuccessRate(),
            ],
        ];
    }

    /**
     * Get error count for a time period.
     */
    private function getErrorCount(Carbon $since): int
    {
        // This would typically query your error logs or error tracking system
        // For now, we'll return a placeholder
        return 0;
    }

    /**
     * Get average processing time.
     */
    private function getAverageProcessingTime(): float
    {
        // This would calculate from actual performance metrics
        // For now, return a placeholder
        return 150.0; // ms
    }

    /**
     * Get storage usage.
     */
    private function getStorageUsage(): array
    {
        $disk = config('inbounder.attachments.storage_disk', 'local');
        $path = storage_path('app');

        $totalSpace = disk_total_space($path);
        $freeSpace = disk_free_space($path);
        $usedSpace = $totalSpace - $freeSpace;

        return [
            'total_bytes' => $totalSpace,
            'used_bytes' => $usedSpace,
            'free_bytes' => $freeSpace,
            'usage_percentage' => round(($usedSpace / $totalSpace) * 100, 2),
        ];
    }

    /**
     * Get attachment count.
     */
    private function getAttachmentCount(): int
    {
        return DB::table('inbound_email_attachments')->count();
    }

    /**
     * Get last webhook time.
     */
    private function getLastWebhookTime(): ?string
    {
        $lastEmail = InboundEmail::latest()->first();
        return $lastEmail ? $lastEmail->created_at->toISOString() : null;
    }

    /**
     * Get webhook success rate.
     */
    private function getWebhookSuccessRate(): float
    {
        $lastHour = Carbon::now()->subHour();

        $totalEmails = InboundEmail::where('created_at', '>=', $lastHour)->count();
        $failedEmails = InboundEmail::where('created_at', '>=', $lastHour)
            ->whereNotNull('error_message')
            ->count();

        if ($totalEmails === 0) {
            return 100.0;
        }

        return round((($totalEmails - $failedEmails) / $totalEmails) * 100, 2);
    }

    /**
     * Get system alerts.
     */
    public function getAlerts(): array
    {
        $alerts = [];
        $now = Carbon::now();
        $lastHour = $now->copy()->subHour();

        // Check for high error rate
        $errorCount = $this->getErrorCount($lastHour);
        if ($errorCount > 10) {
            $alerts[] = [
                'type' => 'error',
                'message' => "High error rate detected: {$errorCount} errors in the last hour",
                'timestamp' => $now->toISOString(),
            ];
        }

        // Check for storage issues
        $storageUsage = $this->getStorageUsage();
        if ($storageUsage['usage_percentage'] > 90) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Storage usage is high: {$storageUsage['usage_percentage']}%",
                'timestamp' => $now->toISOString(),
            ];
        }

        // Check for low webhook success rate
        $successRate = $this->getWebhookSuccessRate();
        if ($successRate < 95) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Low webhook success rate: {$successRate}%",
                'timestamp' => $now->toISOString(),
            ];
        }

        return $alerts;
    }

    /**
     * Get performance metrics for a time period.
     */
    public function getPerformanceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $operations = ['email_processing', 'attachment_processing', 'webhook_handling'];
        $metrics = [];

        foreach ($operations as $operation) {
            $operationMetrics = $this->getOperationMetrics($operation, $startDate, $endDate);
            $metrics[$operation] = $operationMetrics;
        }

        return $metrics;
    }

    /**
     * Get metrics for a specific operation.
     */
    private function getOperationMetrics(string $operation, Carbon $startDate, Carbon $endDate): array
    {
        // This would query your performance metrics storage
        // For now, return placeholder data
        return [
            'total_operations' => 100,
            'average_duration_ms' => 150.0,
            'max_duration_ms' => 500.0,
            'min_duration_ms' => 50.0,
            'success_rate' => 98.5,
            'error_count' => 2,
        ];
    }
}
