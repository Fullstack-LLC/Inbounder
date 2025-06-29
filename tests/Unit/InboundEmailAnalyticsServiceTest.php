<?php

namespace Fullstack\Inbounder\Tests\Unit;

use Carbon\Carbon;
use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Models\InboundEmailEvent;
use Fullstack\Inbounder\Services\InboundEmailAnalyticsService;
use Fullstack\Inbounder\Tests\Helpers\MockTenant;
use Fullstack\Inbounder\Tests\Helpers\MockUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Orchestra\Testbench\TestCase;

class InboundEmailAnalyticsServiceTest extends TestCase
{
    use DatabaseMigrations;

    private InboundEmailAnalyticsService $analyticsService;

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
        $this->analyticsService = new InboundEmailAnalyticsService();
        $this->createTestEmails();
    }

    /** @test */
    public function it_returns_analytics_for_date_range()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $analytics = $this->analyticsService->getAnalytics($startDate, $endDate);

        $this->assertArrayHasKey('period', $analytics);
        $this->assertArrayHasKey('summary', $analytics);
        $this->assertArrayHasKey('events', $analytics);
        $this->assertArrayHasKey('geography', $analytics);
        $this->assertArrayHasKey('devices', $analytics);
        $this->assertArrayHasKey('clients', $analytics);
        $this->assertArrayHasKey('daily_trends', $analytics);

        $this->assertEquals(3, $analytics['summary']['total_emails']);
        $this->assertEquals(6, $analytics['summary']['total_events']);
        $this->assertEquals(100.0, $analytics['summary']['open_rate']);
    }

    /** @test */
    public function it_filters_analytics_by_tenant()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $analytics = $this->analyticsService->getAnalytics($startDate, $endDate, 1);

        $this->assertEquals(3, $analytics['summary']['total_emails']);

        // Test with non-existent tenant
        $analytics = $this->analyticsService->getAnalytics($startDate, $endDate, 999);
        $this->assertEquals(0, $analytics['summary']['total_emails']);
    }

    /** @test */
    public function it_calculates_correct_summary_statistics()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $analytics = $this->analyticsService->getAnalytics($startDate, $endDate);
        $summary = $analytics['summary'];

        $this->assertEquals(3, $summary['total_emails']);
        $this->assertEquals(6, $summary['total_events']);
        $this->assertEquals(3, $summary['delivered']);
        $this->assertEquals(3, $summary['opened']);
        $this->assertEquals(0, $summary['clicked']);
        $this->assertEquals(0, $summary['bounced']);
        $this->assertEquals(100.0, $summary['open_rate']);
        $this->assertEquals(0, $summary['click_rate']);
        $this->assertEquals(0, $summary['bounce_rate']);
    }

    /** @test */
    public function it_handles_zero_emails_correctly()
    {
        // Clear all data
        InboundEmail::query()->delete();
        InboundEmailEvent::query()->delete();

        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $analytics = $this->analyticsService->getAnalytics($startDate, $endDate);
        $summary = $analytics['summary'];

        $this->assertEquals(0, $summary['total_emails']);
        $this->assertEquals(0, $summary['total_events']);
        $this->assertEquals(0, $summary['open_rate']);
        $this->assertEquals(0, $summary['click_rate']);
        $this->assertEquals(0, $summary['bounce_rate']);
    }

    /** @test */
    public function it_returns_geographic_data()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $analytics = $this->analyticsService->getAnalytics($startDate, $endDate);
        $geography = $analytics['geography'];

        $this->assertArrayHasKey('United States', $geography);
        $this->assertEquals(6, $geography['United States']['count']);
        $this->assertArrayHasKey('regions', $geography['United States']);
        $this->assertArrayHasKey('California', $geography['United States']['regions']);
        $this->assertArrayHasKey('New York', $geography['United States']['regions']);
    }

    /** @test */
    public function it_returns_device_data()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $analytics = $this->analyticsService->getAnalytics($startDate, $endDate);
        $devices = $analytics['devices'];

        $this->assertEquals(3, $devices['desktop']);
        $this->assertEquals(3, $devices['mobile']);
    }

    /** @test */
    public function it_returns_client_data()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $analytics = $this->analyticsService->getAnalytics($startDate, $endDate);
        $clients = $analytics['clients'];

        $this->assertEquals(3, $clients['Gmail']);
        $this->assertEquals(3, $clients['iPhone Mail']);
    }

    /** @test */
    public function it_returns_daily_trends()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $analytics = $this->analyticsService->getAnalytics($startDate, $endDate);
        $trends = $analytics['daily_trends'];

        $this->assertIsArray($trends);
        $this->assertGreaterThan(0, count($trends));

        foreach ($trends as $trend) {
            $this->assertArrayHasKey('date', $trend);
            $this->assertArrayHasKey('emails', $trend);
            $this->assertArrayHasKey('delivered', $trend);
            $this->assertArrayHasKey('opened', $trend);
            $this->assertArrayHasKey('clicked', $trend);
            $this->assertArrayHasKey('bounced', $trend);
        }
    }

    /** @test */
    public function it_exports_analytics_to_csv()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $csv = $this->analyticsService->exportToCsv($startDate, $endDate);

        $this->assertIsString($csv);
        $this->assertStringContainsString('Metric,Value', $csv);
        $this->assertStringContainsString('"Total emails",3', $csv);
        $this->assertStringContainsString('"Open rate",100', $csv);
        $this->assertStringContainsString('Top Countries', $csv);
        $this->assertStringContainsString('"United States",6', $csv);
        $this->assertStringContainsString('Device Types', $csv);
        $this->assertStringContainsString('desktop,3', $csv);
    }

    /** @test */
    public function it_exports_analytics_to_csv_with_tenant_filter()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $csv = $this->analyticsService->exportToCsv($startDate, $endDate, 1);

        $this->assertIsString($csv);
        $this->assertStringContainsString('"Total emails",3', $csv);

        // Test with non-existent tenant
        $csv = $this->analyticsService->exportToCsv($startDate, $endDate, 999);
        $this->assertStringContainsString('"Total emails",0', $csv);
    }

    /** @test */
    public function it_returns_real_time_metrics()
    {
        $metrics = $this->analyticsService->getRealTimeMetrics();

        $this->assertArrayHasKey('emails_last_hour', $metrics);
        $this->assertArrayHasKey('emails_last_24_hours', $metrics);
        $this->assertArrayHasKey('average_emails_per_hour', $metrics);

        $this->assertIsInt($metrics['emails_last_hour']);
        $this->assertIsInt($metrics['emails_last_24_hours']);
        $this->assertIsFloat($metrics['average_emails_per_hour']);
    }

    /** @test */
    public function it_returns_real_time_metrics_with_tenant_filter()
    {
        $metrics = $this->analyticsService->getRealTimeMetrics(1);

        $this->assertArrayHasKey('emails_last_hour', $metrics);
        $this->assertArrayHasKey('emails_last_24_hours', $metrics);
        $this->assertArrayHasKey('average_emails_per_hour', $metrics);

        // Test with non-existent tenant
        $metrics = $this->analyticsService->getRealTimeMetrics(999);
        $this->assertEquals(0, $metrics['emails_last_hour']);
        $this->assertEquals(0, $metrics['emails_last_24_hours']);
    }

    private function createTestEmails(): void
    {
        // Create test emails
        $emails = [
            [
                'message_id' => '<email1@example.com>',
                'from_email' => 'sender1@example.com',
                'to_email' => 'recipient1@example.com',
                'subject' => 'Test Email 1',
                'sender_id' => 1,
                'tenant_id' => 1,
                'created_at' => Carbon::now()->subDays(5),
            ],
            [
                'message_id' => '<email2@example.com>',
                'from_email' => 'sender2@example.com',
                'to_email' => 'recipient2@example.com',
                'subject' => 'Test Email 2',
                'sender_id' => 1,
                'tenant_id' => 1,
                'created_at' => Carbon::now()->subDays(3),
            ],
            [
                'message_id' => '<email3@example.com>',
                'from_email' => 'sender3@example.com',
                'to_email' => 'recipient3@example.com',
                'subject' => 'Test Email 3',
                'sender_id' => 1,
                'tenant_id' => 1,
                'created_at' => Carbon::now()->subDays(1),
            ],
        ];

        foreach ($emails as $emailData) {
            $email = InboundEmail::create($emailData);

            // Create events for each email
            $events = [
                [
                    'event_type' => 'delivered',
                    'ip_address' => '192.168.1.1',
                    'country' => 'United States',
                    'region' => 'California',
                    'city' => 'San Francisco',
                    'device_type' => 'desktop',
                    'client_type' => 'webmail',
                    'client_name' => 'Gmail',
                    'occurred_at' => Carbon::now()->subDays(4),
                ],
                [
                    'event_type' => 'opened',
                    'ip_address' => '192.168.1.2',
                    'country' => 'United States',
                    'region' => 'New York',
                    'city' => 'New York',
                    'device_type' => 'mobile',
                    'client_type' => 'mobile',
                    'client_name' => 'iPhone Mail',
                    'occurred_at' => Carbon::now()->subDays(2),
                ],
            ];

            foreach ($events as $eventData) {
                InboundEmailEvent::create(array_merge($eventData, [
                    'inbound_email_id' => $email->id,
                ]));
            }
        }
    }
}
