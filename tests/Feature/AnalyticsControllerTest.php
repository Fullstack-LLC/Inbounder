<?php

namespace Fullstack\Inbounder\Tests\Feature;

use Carbon\Carbon;
use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Models\InboundEmailEvent;
use Fullstack\Inbounder\Services\InboundEmailAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Fullstack\Inbounder\Tests\TestCase;

class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @test */
    public function it_returns_analytics_with_tenant_filter()
    {
        $response = $this->getJson('/api/webhooks/mailgun/analytics?' . http_build_query([
            'start_date' => Carbon::now()->subDays(7)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
            'tenant_id' => 1,
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'summary',
                'geography',
                'devices',
                'clients',
                'daily_trends',
            ]);
        $data = $response->json();
        $this->assertEquals(3, $data['summary']['total_emails']);
        $this->assertEquals(6, $data['summary']['total_events']);
        $this->assertEquals(100, $data['summary']['open_rate']);
    }

    /** @test */
    public function it_validates_required_parameters()
    {
        $response = $this->getJson('/api/webhooks/mailgun/analytics');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    /** @test */
    public function it_validates_date_range()
    {
        $response = $this->getJson('/api/webhooks/mailgun/analytics?' . http_build_query([
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->subDays(1)->toDateString(),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    /** @test */
    public function it_returns_real_time_metrics_with_tenant_filter()
    {
        $response = $this->getJson('/api/webhooks/mailgun/analytics/realtime?' . http_build_query([
            'tenant_id' => 1,
        ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function it_exports_analytics_to_csv_with_tenant_filter()
    {
        $response = $this->getJson('/api/webhooks/mailgun/analytics/export?' . http_build_query([
            'start_date' => Carbon::now()->subDays(7)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
            'tenant_id' => 1,
        ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function it_validates_senders_parameters()
    {
        $response = $this->getJson('/api/webhooks/mailgun/analytics/senders?' . http_build_query([
            'start_date' => Carbon::now()->subDays(7)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
            'limit' => 0, // Invalid limit
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['limit']);
    }

    /** @test */
    public function it_returns_geographic_distribution_with_tenant_filter()
    {
        $response = $this->getJson('/api/webhooks/mailgun/analytics/geography?' . http_build_query([
            'start_date' => Carbon::now()->subDays(7)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
            'tenant_id' => 1,
        ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function it_returns_device_distribution_with_tenant_filter()
    {
        $response = $this->getJson('/api/webhooks/mailgun/analytics/devices?' . http_build_query([
            'start_date' => Carbon::now()->subDays(7)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
            'tenant_id' => 1,
        ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function it_handles_empty_analytics_data()
    {
        // Clear all data
        InboundEmail::query()->delete();
        InboundEmailEvent::query()->delete();

        $response = $this->getJson('/api/webhooks/mailgun/analytics?' . http_build_query([
            'start_date' => Carbon::now()->subDays(7)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, $data['summary']['total_emails']);
        $this->assertEquals(0, $data['summary']['total_events']);
    }

    /** @test */
    public function it_handles_analytics_service_exception()
    {
        // Mock the analytics service to throw an exception
        $this->mock(InboundEmailAnalyticsService::class, function ($mock) {
            $mock->shouldReceive('getAnalytics')
                ->once()
                ->andThrow(new \Exception('Analytics service error'));
        });

        $response = $this->getJson('/api/webhooks/mailgun/analytics?' . http_build_query([
            'start_date' => Carbon::now()->subDays(7)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
        ]));

        $response->assertStatus(500);
    }
}
