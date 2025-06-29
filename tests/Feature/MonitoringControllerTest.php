<?php

namespace Fullstack\Inbounder\Tests\Feature;

use Carbon\Carbon;
use Fullstack\Inbounder\Services\InboundEmailMonitoringService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Config;
use Fullstack\Inbounder\Tests\TestCase;

class MonitoringControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected function getPackageProviders($app)
    {
        return [
            \Fullstack\Inbounder\InbounderServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestData();
    }

    /** @test */
    public function it_returns_health_check()
    {
        $response = $this->getJson('/api/webhooks/mailgun/monitoring/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'database' => [
                    'status',
                    'connection',
                ],
                'performance' => [
                    'emails_last_hour',
                    'events_last_hour',
                    'errors_last_hour',
                    'average_processing_time_ms',
                ],
                'storage' => [
                    'disk_usage' => [
                        'total_bytes',
                        'used_bytes',
                        'free_bytes',
                        'usage_percentage',
                    ],
                    'attachment_count',
                ],
                'webhooks' => [
                    'last_webhook_received',
                    'webhook_success_rate',
                ],
            ]);

        $data = $response->json();
        $this->assertContains($data['status'], ['healthy', 'degraded']);
        $this->assertEquals('testbench', $data['database']['connection']);
    }

    /** @test */
    public function it_returns_503_when_health_check_fails()
    {
        // Mock the monitoring service to return unhealthy status
        $this->mock(InboundEmailMonitoringService::class, function ($mock) {
            $mock->shouldReceive('getHealthCheck')
                ->once()
                ->andReturn([
                    'status' => 'unhealthy',
                    'timestamp' => now()->toISOString(),
                    'database' => ['status' => 'unhealthy'],
                    'performance' => [],
                    'storage' => [],
                    'webhooks' => [],
                ]);
        });

        $response = $this->getJson('/api/webhooks/mailgun/monitoring/health');

        $response->assertStatus(503);
    }

    /** @test */
    public function it_handles_monitoring_service_exception()
    {
        // Mock the monitoring service to throw an exception
        $this->mock(InboundEmailMonitoringService::class, function ($mock) {
            $mock->shouldReceive('getHealthCheck')
                ->once()
                ->andThrow(new \Exception('Monitoring service error'));
        });

        $response = $this->getJson('/api/webhooks/mailgun/monitoring/health');

        $response->assertStatus(500);
    }

    /** @test */
    public function it_handles_alerts_service_exception()
    {
        // Mock the monitoring service to throw an exception
        $this->mock(InboundEmailMonitoringService::class, function ($mock) {
            $mock->shouldReceive('getAlerts')
                ->once()
                ->andThrow(new \Exception('Alerts service error'));
        });

        $response = $this->getJson('/api/webhooks/mailgun/monitoring/alerts');

        $response->assertStatus(500);
    }

    private function createTestData(): void
    {
        // Create some test data for monitoring
        // This can be expanded as needed
    }
}
