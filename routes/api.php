<?php

use Fullstack\Inbounder\Controllers\AnalyticsController;
use Fullstack\Inbounder\Controllers\InboundMailController;
use Fullstack\Inbounder\Controllers\MonitoringController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inbounder API Routes
|--------------------------------------------------------------------------
|
| These routes handle inbound email processing and analytics.
|
*/

// Webhook endpoint for Mailgun
Route::post('/', InboundMailController::class)->name('inbounder.webhook');

// Analytics endpoints
Route::prefix('analytics')->group(function () {
    Route::get('/', [AnalyticsController::class, 'getAnalytics'])->name('inbounder.analytics');
    Route::get('/realtime', [AnalyticsController::class, 'getRealTimeMetrics'])->name('inbounder.analytics.realtime');
    Route::get('/export', [AnalyticsController::class, 'exportCsv'])->name('inbounder.analytics.export');
    Route::get('/senders', [AnalyticsController::class, 'getTopSenders'])->name('inbounder.analytics.senders');
    Route::get('/geography', [AnalyticsController::class, 'getGeographicDistribution'])->name('inbounder.analytics.geography');
    Route::get('/devices', [AnalyticsController::class, 'getDeviceDistribution'])->name('inbounder.analytics.devices');
});

// Monitoring endpoints
Route::prefix('monitoring')->group(function () {
    Route::get('/health', [MonitoringController::class, 'healthCheck'])->name('inbounder.monitoring.health');
    Route::get('/alerts', [MonitoringController::class, 'getAlerts'])->name('inbounder.monitoring.alerts');
    Route::get('/performance', [MonitoringController::class, 'getPerformanceMetrics'])->name('inbounder.monitoring.performance');
    Route::get('/status', [MonitoringController::class, 'getSystemStatus'])->name('inbounder.monitoring.status');
});
