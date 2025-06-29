<?php

namespace Fullstack\Inbounder\Tests\Unit;

use Carbon\Carbon;
use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Models\InboundEmailEvent;
use Fullstack\Inbounder\Services\InboundEmailMonitoringService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;

class InboundEmailMonitoringServiceTest extends TestCase
{
    use DatabaseMigrations;

    private InboundEmailMonitoringService $monitoringService;

    protected function getPackageProviders($app)
    {
        return [
            \Fullstack\Inbounder\InbounderServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $dbPath = base_path('vendor/orchestra/testbench-core/laravel/testbench.sqlite');
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => $dbPath,
            'prefix' => '',
        ]);
        $app['config']->set('inbounder.models.tenant', \Fullstack\Inbounder\Tests\Helpers\MockTenant::class);
        $app['config']->set('inbounder.models.user', \Fullstack\Inbounder\Tests\Helpers\MockUser::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitoringService = new InboundEmailMonitoringService();
    }

    /** @test */
    public function it_logs_operations()
    {
        $operation = 'test_operation';
        $data = ['key' => 'value'];
        $level = 'info';

        // Should not throw an exception
        $this->monitoringService->logOperation($operation, $data, $level);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_tracks_performance()
    {
        $operation = 'test_operation';
        $result = $this->monitoringService->trackPerformance($operation, function () {
            return 'test_result';
        });

        $this->assertEquals('test_result', $result);
    }

    /** @test */
    public function it_tracks_performance_with_exception()
    {
        $operation = 'test_operation';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->monitoringService->trackPerformance($operation, function () {
            throw new \Exception('Test exception');
        });
    }

    /** @test */
    public function it_returns_health_check()
    {
        $healthCheck = $this->monitoringService->getHealthCheck();

        $this->assertArrayHasKey('status', $healthCheck);
        $this->assertArrayHasKey('timestamp', $healthCheck);
        $this->assertArrayHasKey('database', $healthCheck);
        $this->assertArrayHasKey('performance', $healthCheck);
        $this->assertArrayHasKey('storage', $healthCheck);
        $this->assertArrayHasKey('webhooks', $healthCheck);

        $this->assertIsString($healthCheck['status']);
        $this->assertIsString($healthCheck['timestamp']);
        $this->assertIsArray($healthCheck['database']);
        $this->assertIsArray($healthCheck['performance']);
        $this->assertIsArray($healthCheck['storage']);
        $this->assertIsArray($healthCheck['webhooks']);
    }

    /** @test */
    public function it_returns_system_alerts()
    {
        $alerts = $this->monitoringService->getAlerts();

        $this->assertIsArray($alerts);

        // Check that each alert has the required structure
        foreach ($alerts as $alert) {
            $this->assertArrayHasKey('type', $alert);
            $this->assertArrayHasKey('message', $alert);
            $this->assertArrayHasKey('timestamp', $alert);
            $this->assertIsString($alert['type']);
            $this->assertIsString($alert['message']);
            $this->assertIsString($alert['timestamp']);
        }
    }

    /** @test */
    public function it_returns_performance_metrics()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $metrics = $this->monitoringService->getPerformanceMetrics($startDate, $endDate);

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('email_processing', $metrics);
        $this->assertArrayHasKey('attachment_processing', $metrics);
        $this->assertArrayHasKey('webhook_handling', $metrics);

        foreach ($metrics as $operation => $operationMetrics) {
            $this->assertArrayHasKey('total_operations', $operationMetrics);
            $this->assertArrayHasKey('average_duration_ms', $operationMetrics);
            $this->assertArrayHasKey('max_duration_ms', $operationMetrics);
            $this->assertArrayHasKey('min_duration_ms', $operationMetrics);
            $this->assertArrayHasKey('success_rate', $operationMetrics);
            $this->assertArrayHasKey('error_count', $operationMetrics);
        }
    }

    /** @test */
    public function it_handles_health_check_with_recent_data()
    {
        // Create some test data
        InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test Email',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $healthCheck = $this->monitoringService->getHealthCheck();

        $this->assertArrayHasKey('performance', $healthCheck);
        $this->assertArrayHasKey('emails_last_hour', $healthCheck['performance']);
        $this->assertArrayHasKey('events_last_hour', $healthCheck['performance']);
        $this->assertArrayHasKey('errors_last_hour', $healthCheck['performance']);
        $this->assertArrayHasKey('average_processing_time_ms', $healthCheck['performance']);
    }

    /** @test */
    public function it_handles_storage_usage_calculation()
    {
        $healthCheck = $this->monitoringService->getHealthCheck();

        $this->assertArrayHasKey('storage', $healthCheck);
        $this->assertArrayHasKey('disk_usage', $healthCheck['storage']);
        $this->assertArrayHasKey('attachment_count', $healthCheck['storage']);

        $diskUsage = $healthCheck['storage']['disk_usage'];
        $this->assertArrayHasKey('total_bytes', $diskUsage);
        $this->assertArrayHasKey('used_bytes', $diskUsage);
        $this->assertArrayHasKey('free_bytes', $diskUsage);
        $this->assertArrayHasKey('usage_percentage', $diskUsage);

        $this->assertTrue(is_int($diskUsage['total_bytes']) || is_float($diskUsage['total_bytes']));
        $this->assertTrue(is_int($diskUsage['used_bytes']) || is_float($diskUsage['used_bytes']));
        $this->assertTrue(is_int($diskUsage['free_bytes']) || is_float($diskUsage['free_bytes']));
        $this->assertIsFloat($diskUsage['usage_percentage']);
    }

    /** @test */
    public function it_handles_webhook_metrics()
    {
        $healthCheck = $this->monitoringService->getHealthCheck();

        $this->assertArrayHasKey('webhooks', $healthCheck);
        $this->assertArrayHasKey('last_webhook_received', $healthCheck['webhooks']);
        $this->assertArrayHasKey('webhook_success_rate', $healthCheck['webhooks']);

        $this->assertIsFloat($healthCheck['webhooks']['webhook_success_rate']);
        $this->assertGreaterThanOrEqual(0, $healthCheck['webhooks']['webhook_success_rate']);
        $this->assertLessThanOrEqual(100, $healthCheck['webhooks']['webhook_success_rate']);
    }

    /** @test */
    public function it_handles_database_connection_check()
    {
        $healthCheck = $this->monitoringService->getHealthCheck();

        $this->assertArrayHasKey('database', $healthCheck);
        $this->assertArrayHasKey('status', $healthCheck['database']);
        $this->assertArrayHasKey('connection', $healthCheck['database']);

        $this->assertIsString($healthCheck['database']['status']);
        $this->assertIsString($healthCheck['database']['connection']);
    }
}
